<?php

namespace Uzsoftic\SitemapGenerator;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Search;
use App\Models\SubCategory;
use App\Models\SubSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class Sitemap extends Controller
{

    public $sitemap_start = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    public $sitemap_end = '
</urlset>';
    public $sitemap_index_start = '<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    public $sitemap_index_end = '
</sitemapindex>';

    public function index(Request $request){
        return \Illuminate\Support\Facades\Redirect::to('storage/sitemap.xml');
    }

    public function startByTerminal(){
        return $this->generate();
    }

    public function generate(){
        $savedFileList = $this->make(['pages', 'categories', 'products', 'brands', 'blogs', 'search'], 'https://openshop.uz', 'sitemap');
        $status = $this->makeMain($savedFileList, 'https://openshop.uz/public/storage/', 'sitemap.xml');

        //$savedFileListMobile = $this->make(['pages', 'categories', 'products', 'brands', 'blogs', 'search'], 'https://m.openshop.uz', 'sitemap/mobile');
        //$status = $this->makeMain($savedFileListMobile, 'https://m.openshop.uz/public/', 'mobile_sitemap.xml');

        //foreach (array_merge($savedFileList, $savedFileListMobile) as $file){
        //    dump('https://openshop.uz/public/'.$file.' is saved successfully.');
        //}
        foreach ($savedFileList as $file){
            dump('https://openshop.uz/public/storage/'.$file.' is saved successfully.');
        }

        if($status){
            return 1;
        }
        return 0;
    }

    public function makeMain($array, $link = 'https://openshop.uz/public/storage/', $output = 'sitemap.xml'){
        $sitemap = $this->sitemap_index_start;
        foreach($array as $key => $element){
            $sitemap .= '<sitemap>
      <loc>'.$link.$element.'</loc>
      <lastmod>'.now()->format('c').'</lastmod>
    </sitemap>';
        }
        $sitemap .= $this->sitemap_index_end;
        if(Storage::put('public/'.$output, $sitemap)){
            return 1;
        }
        return 0;
    }

    public function make($settings, $slink = 'https://openshop.uz', $output_folder = 'sitemap'){
        $files = [];
        $i = 0;
        $page = 1;
        $limit = 5000;

        if(in_array('pages', $settings)){
            $url = [
                '/',
                '/blog',
                '/search',
                '/cart',
                '/shop/categories',
                '/page/privacy_policy',
                '/page/other_conditions',
                '/page/payment_info',
                '/page/return_policy',
                '/page/checkout_info',
                '/page/b2b_sales',
                '/auth/login',
                '/auth/register',
                '/uploads/brandbookopenshop.pdf',
            ];
            $name = 'page';
            $link = $slink;
            $sitemap = $this->sitemap_start;
            foreach($url as $key => $element){
                $loc = $link.$element;
                $lastmod = now()->format('c');

                $sitemap .= $this->template($loc, $lastmod, 'daily', '1.0');
            }
            $sitemap .= $this->sitemap_end;
            if(Storage::put('public/'.$output_folder.'/'.$name.$page.'.xml', $sitemap)){
                $files[] = $output_folder.'/'.$name.$page.'.xml';
            }
            $i = 0;
            $page = 1;
        } // 1.0 daily

        if(in_array('categories', $settings)){
            $name = 'category';
            $sitemap = $this->sitemap_start;
            //GET VALUES
            $categories = Category::where('deleted', '0')->get();
            $subcategories = SubCategory::where('deleted', '0')->get();
            $subsubcategories = SubSubCategory::where('deleted', '0')->get();
            //$categories = $categories->merge($subcategories)->merge($subsubcategories)->all();

            $link = $slink.'/shop/category/';
            foreach($categories as $key => $element){
                $loc = $link.$element->slug;
                $lastmod = $element->updated_at->format('c');
                $sitemap .= $this->template($loc, $lastmod, 'weekly', '0.9');
            }

            $link = $slink.'/shop/subcategory/';
            foreach($subcategories as $key => $element){
                $loc = $link.$element->slug;
                $lastmod = $element->updated_at->format('c');
                $sitemap .= $this->template($loc, $lastmod, 'weekly', '0.9');
            }

            $link = $slink.'/shop/subsubcategory/';
            foreach($subsubcategories as $key => $element){
                $loc = $link.$element->slug;
                $lastmod = $element->updated_at->format('c');
                $sitemap .= $this->template($loc, $lastmod, 'weekly', '0.9');
            }

            $sitemap .= $this->sitemap_end;
            if(Storage::put('public/'.$output_folder.'/'.$name.$page.'.xml', $sitemap)){
                $files[] = $output_folder.'/'.$name.$page.'.xml';
            }
            $i = 0;
            $page = 1;
        }

        if(in_array('products', $settings)){
            $name = 'product';
            $link = $slink.'/shop/product/';
            $sitemap = $this->sitemap_start;
            foreach(Product::where('deleted', '0')->where('moderated', 0)->where('published', 1)->where('current_stock', '!=', 0)->get() as $key => $element){
                foreach(['uz', 'ru', 'en'] as $lang){
                    $i++;
                    if ($i % $limit == 0) {
                        $sitemap .= $this->sitemap_end;
                        if(Storage::put('public/'.$output_folder.'/'.$name.$page.'.xml', $sitemap)){
                            $files[] = $output_folder.'/'.$name.$page.'.xml';
                        }
                        $page++;
                        $sitemap = $this->sitemap_start;
                    }
                    $loc = $link.$element->slug;
                    $lastmod = $element->updated_at->format('c');

                    $sitemap .= $this->template($loc, $lastmod, 'daily', '0.8', '?lang='.$lang);
                }
            }
            $sitemap .= $this->sitemap_end;
            if(Storage::put('public/'.$output_folder.'/'.$name.$page.'.xml', $sitemap)){
                $files[] = $output_folder.'/'.$name.$page.'.xml';
            }
            $i = 0;
            $page = 1;
        }

        if(in_array('search', $settings)){
            $name = 'search';
            $link = $slink.'/search?q=';
            $sitemap = $this->sitemap_start;
            foreach(Search::all() as $key => $element){
                $i++;
                if ($i % $limit == 0) {
                    $sitemap .= $this->sitemap_end;
                    if(Storage::put('public/'.$output_folder.'/'.$name.$page.'.xml', $sitemap)){
                        $files[] = $output_folder.'/'.$name.$page.'.xml';
                    }
                    $page++;
                    $sitemap = $this->sitemap_start;
                }
                $loc = $link.str_replace([' ', '&'], ['+', '&amp;'], preg_replace('/[^\p{L}\p{N}\s]/u', '', $element->query));
                $lastmod = $element->updated_at->format('c');

                $sitemap .= $this->template($loc, $lastmod, 'daily', '0.8');
            }
            $sitemap .= $this->sitemap_end;
            if(Storage::put('public/'.$output_folder.'/'.$name.$page.'.xml', $sitemap)){
                $files[] = $output_folder.'/'.$name.$page.'.xml';
            }
            $i = 0;
            $page = 1;
        }

        if(in_array('brands', $settings)){
            $name = 'brand';
            $link = $slink.'/shop/brand/';
            $sitemap = $this->sitemap_start;
            foreach(Brand::all() as $key => $element){
                $loc = $link.$element->slug;
                $lastmod = $element->updated_at->format('c');

                $sitemap .= $this->template($loc, $lastmod, 'weekly', '0.8');
            }
            $sitemap .= $this->sitemap_end;
            if(Storage::put('public/'.$output_folder.'/'.$name.$page.'.xml', $sitemap)){
                $files[] = $output_folder.'/'.$name.$page.'.xml';
            }
            $i = 0;
            $page = 1;
        }

        if(in_array('blogs', $settings)){
            $name = 'blog';
            $link = $slink.'/blog/';
            $sitemap = $this->sitemap_start;
            foreach(Blog::where('deleted', '0')->get() as $key => $element){
                $loc = $link.$element->slug;
                $lastmod = $element->updated_at->format('c');

                $sitemap .= $this->template($loc, $lastmod, 'weekly', '0.8');
            }
            $sitemap .= $this->sitemap_end;
            if(Storage::put('public/'.$output_folder.'/'.$name.$page.'.xml', $sitemap)){
                $files[] = $output_folder.'/'.$name.$page.'.xml';
            }
            $i = 0;
            $page = 1;
        }

        return $files;
    }

    public function template($loc, $lastmod, $changefreq = 'weekly', $priority = '0.8', $addlink = null){
        if($addlink != null){
            $loc = $loc.$addlink;
        }
        return '
    <url>
        <loc>'.$loc.'</loc>
        <lastmod>'.$lastmod.'</lastmod>
        <changefreq>'.$changefreq.'</changefreq>
        <priority>'.$priority.'</priority>
    </url>';
    }

    public function setSitemaps(){
        $google = 'http://www.google.com/ping?sitemap=https://openshop.uz/public/storage/sitemap.xml';
    }

}
