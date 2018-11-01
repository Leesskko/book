<?php

namespace App\Console\Commands;

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
        //->find("td[bgcolor='#D6EEF2']")
        //->dump();
        $catarr = explode('<td width="100%" align="center" bgcolor="#D6EEF2" colspan="8">', $res);
        foreach ($catarr as $cat) {
            $html = '<td width="100%" align="center" bgcolor="#D6EEF2" colspan="8">' . $cat;
            $catname = QueryList::setHtml($html)->find("td[bgcolor='#D6EEF2']")->text();
            echo $catname;
        }

    }
}
