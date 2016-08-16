<?php

namespace Laravolt\IndonesiaLogo\Commands;

use Illuminate\Console\Command;

class CrawlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravolt:indonesialogo:crawl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will crawl wikipedia to get logo image file';

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
        $this->line('Wait a minute....');

        $urlWikipedia = 'https://id.wikipedia.org';
        $urlWikipediaIndonesia = $urlWikipedia. '/wiki/Daftar_kabupaten_dan_kota_di_Indonesia';

        //path untuk menyimpan logo
        $path = public_path().'/images';
        //cek folder
        if(!\File::exists($path)) {
            \File::makeDirectory($path, $mode = 0777, true, true);
        }

        try {
            $html = file_get_contents($urlWikipediaIndonesia);
            libxml_use_internal_errors(TRUE);

            $doc = new \DOMDocument();
            $doc->loadHTML($html);
            libxml_clear_errors();

            //memulai crawl
            $tables = $doc->getElementsByTagName('table');
            //mengambil nama province dari wikipedia
            $name_provinces = $this->get_all_name_province($doc);

            $count = 0;
            $id_province = 0;

            foreach ($tables as $table) {
                $trs = $table->getElementsByTagName('tr');

                foreach ($trs as $tr) {
                    $tds = $tr->getElementsByTagName('td');
                    $imgs = $tr->getElementsByTagName('img');

                    if ($tds->length > 1 && $imgs->length != 0) {
                    //mengambil logo kota atau kabupaten dari body tabel
                        $td = $tds->item(1)->childNodes;

                        if ($td->length != 3) {
                            //nama kota atau kabupaten ada dikolom ke2 pada tabel
                            //contoh pada wikipedia tabel provinsi Daerah Khusus Ibukota Jakarta
                            $value = $tds->item(1)->nodeValue;
                        } else {
                            //nama kota atau kabupaten ada dikolom ke3 pada tabel
                            //contoh pada wikipedia tabel provinsi Jawa Tengah
                            $value = $tds->item(2)->nodeValue;
                        }

                        if ($value != null) {
                            foreach ($imgs as $img) {
                                $img_parent = $img->parentNode;
                                if ($img_parent->parentNode->nodeName == 'div') {
                                    $link_to_image = $img_parent->getattribute('href');
                                    //mengambil row dari table regencies
                                    $regency = $this->get_regency($value , $id_province);

                                    //cek file
                                    if(count(glob($path.'/'. $regency->id.'.*'))==0){
                                        //menyimpan gambar
                                        $name_image = $this->save_image($this->get_url_image($urlWikipedia . $link_to_image), $regency->id, $path);
                                    }
                                    //cek kolom logo
                                    if ($regency->logo == '') {
                                        //update database
                                        \DB::table('regencies')->where('id', $regency->id)->update(['logo' => $name_image]);
                                    }
                                }
                            }
                        }
                    } else if ($imgs->length != 0) { 
                    //mengambil logo provinsi dari header tabel
                        $img_parent = $imgs->item(0)->parentNode;
                        if ($img_parent->hasAttribute('class')) {
                            if ($img_parent->getAttribute('class') == 'image') {
                                $link_to_image = $img_parent->getattribute('href');
                                 //mengambil row dari table regencies
                                $province = $this->get_province($name_provinces[$count]);
                                $id_province = $province->id;

                                //cek file
                                if(count(glob($path.'/'.$id_province.'.*'))==0){ 
                                    //menyimpan gambar
                                    $name_image = $this->save_image($this->get_url_image($urlWikipedia. $link_to_image), $id_province,$path);
                                }
                                //cek kolom logo
                                if ($province->logo == '') {
                                    //update database
                                    \DB::table('provinces')->where('id', $province->id)->update(['logo' => $name_image]);
                                }
                                $count++;
                            }
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            $this->error('Something wrong');
            return;
        }

        $this->info('Done...');
        
    }

    //mengambil row dari tabel regencies
    function get_regency($value, $id_province){
        $regency = \DB::table('regencies')->where('name', $value)->first();
        if (is_null($regency)) {
            //untuk kasus seperti contoh pada wikipedia Kabupaten Batubara, pada database KABUPATEN BATU BARA
            $sql = "select * from `regencies`  where `province_id` = ".$id_province." and SOUNDEX(`name`) = SOUNDEX('".$value."') limit 1";
            $regency = \DB::select($sql);

            if (count($regency) == 1 ) {
                $regency = $regency[0];
            }
            else{
                //untuk kasus seperti pada wikipedia Kota Administrasi Jakarta Barat, pada database KOTA JAKARTA BARAT
                $value_explode = explode(" ", $value);
                $count_value = count($value_explode);
                for ($x = $count_value - 1; $x > 0; $x--) {
                    if ($x == $count_value - 1) {
                        $string_value = $value_explode[$x];
                    } else {
                        $string_value = substr_replace($string_value, $value_explode[$x] . " ", 0, 0); 
                    }
                    $regencies = \DB::select("select * from `regencies` where `province_id` = ".$id_province." and `name` like '%".$string_value."%' ");
                    if (count($regencies) == 1) {
                        $regency = $regencies[0];
                        break;
                    }
                }
            }
        }
        return $regency;
    }

    //mengambil row dari tabel provinces
    function get_province($name_province){
        $province = \DB::table('provinces')->where('name', $name_province)->first();
        if ($province=== null) {
            //untuk kasus seperti pada wikipedia Daerah Khusus Ibukota Jakarta, pada database DKI JAKARTA
            $explode_provinces = explode(" ", $name_province);
            $cnt = count($explode_provinces) - 1;
            $province = \DB::select("select * from provinces where name like '%".$explode_provinces[$cnt]."%' ");
            $province = $province[0];
        }
        return $province;
    }

    //mengambil nama province dari wikipedia
    function get_all_name_province($doc) {
        $h3s = $doc->getElementsByTagName('h3');
        $name_provinces = array();
        foreach ($h3s as $h3){
            $span = $h3->getElementsByTagName('span');
            if($span->length!=0){
                if($span->item(0)->hasAttribute('class')){
                    if($span->item(0)->getAttribute('class')=='mw-headline'){
                        $name_provinces[] = $span->item(0)->nodeValue;
                    }
                }
            }
        }
        return $name_provinces;
    }

    //mengambil link gambar
    function get_url_image($url) {
        $html = file_get_contents($url);
        $doc = new \DOMDocument();

        if (!empty($html)) { 
            $doc->loadHTML($html);
            libxml_clear_errors();
            $xpath = new \DOMXPath($doc);
            $div = $xpath->query('//div[@id="file"]');
            $link = 'https:'. $div->item(0)->getElementsByTagName('img')->item(0)->getattribute('src');
            return $link;
        }
    }

    function save_image($url, $id, $path) {
        $extension = pathinfo($url,PATHINFO_EXTENSION);
        $name = $id.'.'.$extension;
        
        $file = file_get_contents($url);
        $filename = $path.'/'.$name;
        file_put_contents($filename, $file);
        
        return $name;
    }
}

