<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;

class ParseController extends Controller
{
    const RESOLVE_ATTRIBUTES = ['id', 'class'];
    const RESOLVE_TAGS       = [
        'p',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'table',
        'thead',
        'tbody',
        'tfoot',
        'tr',
        'th',
        'td',
    ];
    
    /**
     * @param \Illuminate\Http\Request $request
     */
    public function parse(Request $request)
    {
        if (empty($request->parseQuery)) {
            // throw new \Exception('empty query!', 502);
            return "empty query!";
        }
        
        $url = $this->getUrl($request->parseQuery);
        
        if ($url === false) {
            // throw new \Exception('Not exists url in query!', 502);
            return "Not exists url in query!";
        }
        
        $attributes = $this->getAttributes($request->parseQuery);
        
        if ($attributes === false) {
            // throw new \Exception('Not exists resolve attributes in query!', 502);
            return "Not exists resolve attributes in query!";
        }
        
        try {
            $content = file_get_contents($url);
            
            return $this->getBlockContent($content, $attributes);
        } catch (\Exception $e) {
            // return "Url недоступен";
            return print_r($e, 1);
        }
    }
    
    /**
     * @param string $query
     *
     * @return bool|mixed
     */
    private function getUrl(string $query)
    {
        $regexp = '/' .
            'https?:\/\/[^\s]+' .
            '/iu';
        
        $matches = [];
        if (preg_match($regexp, $query, $matches)) {
            return $matches[0];
        }
        
        return false;
    }
    
    /**
     * @param string $query
     *
     * @return array|bool
     */
    private function getAttributes(string $query)
    {
        $regexp = '/' .
            '(?<=^|\s)\w{2,5}=["\'][^"\']*["\']' .
            '/iu';
        
        $matches = [];
        if (preg_match_all($regexp, $query, $matches)) {
            $res = [];
            foreach ($matches[0] as $attribut) {
                list($name, $value) = explode('=', $attribut);
                // Добавим только разрешенные атрибуты
                $name = mb_strtolower($name);
                if (in_array($name, self::RESOLVE_ATTRIBUTES)) {
                    $res[] = [
                        'name'  => $name,
                        'value' => trim($value, "\"'"),
                    ];
                }
            }
            
            return !empty($res) ? $res : false;
        }
        
        return false;
    }
    
    /**
     * @param string $pageContent
     * @param array $attributes
     *
     * @return string
     */
    private function getBlockContent(string $pageContent, array $attributes): string
    {
        $crawler = new Crawler($pageContent);
        
        $blockSelector = '';
        foreach ($attributes as $attr) {
            switch ($attr['name']) {
                case 'id':
                    $blockSelector .= "#{$attr['value']} ";
                    break;
                case 'class':
                    $blockSelector .= ".{$attr['value']} ";
                    break;
                default:
                    $blockSelector .= "[{$attr['name']}=\"{$attr['value']}\"] ";
            }
        }
        
        $block = $crawler->filter($blockSelector);
        
        if ($block->count()) {
            $res = '';
            $block->each(function (Crawler $block, $i) use (&$res) {
                // $res .= $i . PHP_EOL;
                $res .= $this->filterBlockContent($block->html()) . PHP_EOL;
            });
            
            return $res;
        }
        
        //throw new \Exception("No exisits block by selector '{$blockSelector}'");
        return "No exisits block by selector '{$blockSelector}'";
    }
    
    /**
     * @param string $blockContent
     *
     * @return string
     */
    private function filterBlockContent(string $blockContent): string
    {
        return preg_replace_callback_array([
            // лишние пробелы
            '/(>?)\s{2,}(<?)/iu' => function ($matches) {
                if (!empty($matches[1]) || !empty($matches[2])) {
                    return $matches[1] . $matches[2];
                }
                
                return " ";
            },
            // теги
            '/<\/?(\w+)(\s[^>])*>/iu'     => function ($matches) {
                // если тег в списке разрешенных, то оставим все как есть
                if (in_array(mb_strtolower($matches[1]), self::RESOLVE_TAGS)) {
                    return $matches[0];
                }
                
                // Пробел нужен при вырезании закрывающего тега
                // Иначе текст может слиться,
                // например <p><b>Какой-то текст.</b>Снова текст</p>
                // превратится в <p>Какой-то текст.Снова текст</p>
                return $matches[0][1] === '/' ? " " : "";
            },
        ], $blockContent);
    }
}
