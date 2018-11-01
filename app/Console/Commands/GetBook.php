<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Console\Command;
use QL\QueryList;

class GetBook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'book:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {


        $ql = QueryList::get('https://www.xbookcn.com')->encoding('UTF-8', 'BIG5')->removeHead();
        $res = $ql->find('body')->find('table')->find('table')->eq(0)->html();
        $this->warn('抓取总目录成功');
        $catarr = explode('<td width="100%" align="center" bgcolor="#D6EEF2" colspan="8">', $res);
        $this->warn('开始解析分类');
        $i = 0;
        foreach ($catarr as $cat) {
            $i++;
            if ($i == 1) {
                continue;
            }
            $html = '<td width="100%" align="center" bgcolor="#D6EEF2" colspan="8">' . $cat;
            $catname = QueryList::setHtml($html)->find("td[bgcolor='#D6EEF2']")->text();
            $this->warn('开始处理分类:' . $catname);
            $category = new Category();
            $category->catname = \MediaWikiZhConverter::convert($catname, "zh-cn");
            $category->save();
            $catid = $category->id;

            $as = QueryList::setHtml($html)->find('a')->map(function ($item) {
                return [
                    'name' => $item->text(),
                    'href' => $item->href
                ];
            });
            $this->warn('分类处理完成');

            foreach ($as->all() as $a) {
                $booklink = "https://www.xbookcn.com/" . $a['href'];
                $q2 = QueryList::get($booklink)->encoding('UTF-8', 'BIG5')->removeHead();
                $bookname = $q2->find('.title_index')->text();
                $this->warn('开始处理书籍' . $bookname);
                $book = new Book();
                $book->category_id = $catid;
                $book->bookname = \MediaWikiZhConverter::convert($bookname, "zh-cn");
                $book->save();
                $bookid = $book->id;
                $articleslinks = $q2->find('.content')->find('a')->map(function ($item) {
                    return [
                        'name' => $item->text(),
                        'href' => $item->href
                    ];
                });
                $this->warn('书籍目录处理完毕');
                $aa = pathinfo($booklink);
                $baseurl = $aa['dirname'];
                foreach ($articleslinks as $ar) {
                    $articleurl = $baseurl . "/" . $ar['href'];
                    $q3 = QueryList::get($articleurl)->encoding('UTF-8', 'BIG5')->removeHead();
                    $title = $q3->find('.title_page')->text();
                    $content = $q3->find('.content')->html();
                    $this->warn("开始抓取[$catname]-[$bookname]-[$title]的内容");
                    $atc = new Article();
                    $atc->bookid = $bookid;
                    $atc->articlename = \MediaWikiZhConverter::convert($title, "zh-cn");
                    $atc->content = \MediaWikiZhConverter::convert($content, "zh-cn");
                    $atc->save();
                    $this->warn("处理完毕 [$catname]-[$bookname]-[$title]");
                }
            }
        }

    }
}
