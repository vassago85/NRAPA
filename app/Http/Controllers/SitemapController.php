<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Public XML sitemap with lastmod hints (helps crawl scheduling; URLs follow APP_URL).
     */
    public function __invoke(): Response
    {
        $urls = [
            ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'weekly', 'file' => resource_path('views/welcome.blade.php')],
            ['route' => 'terms-and-conditions', 'priority' => '0.3', 'changefreq' => 'yearly', 'file' => resource_path('views/pages/terms.blade.php')],
            ['route' => 'privacy-policy', 'priority' => '0.3', 'changefreq' => 'yearly', 'file' => resource_path('views/pages/privacy.blade.php')],
            ['route' => 'info.about', 'priority' => '0.8', 'changefreq' => 'monthly', 'file' => resource_path('views/pages/info/about.blade.php')],
            ['route' => 'info.firearm-licence-process', 'priority' => '0.8', 'changefreq' => 'monthly', 'file' => resource_path('views/pages/info/firearm-licence-process.blade.php')],
            ['route' => 'info.minimum-requirements', 'priority' => '0.7', 'changefreq' => 'monthly', 'file' => resource_path('views/pages/info/minimum-requirements.blade.php')],
            ['route' => 'info.dedicated-procedure', 'priority' => '0.8', 'changefreq' => 'monthly', 'file' => resource_path('views/pages/info/dedicated-procedure.blade.php')],
            ['route' => 'info.shooting-exercises', 'priority' => '0.7', 'changefreq' => 'monthly', 'file' => resource_path('views/pages/info/shooting-exercises.blade.php')],
        ];

        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($urls as $entry) {
            $loc = route($entry['route']);
            $lastmod = isset($entry['file']) && is_file($entry['file'])
                ? date('c', filemtime($entry['file']))
                : now()->toAtomString();

            $lines[] = '  <url>';
            $lines[] = '    <loc>'.htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc>';
            $lines[] = '    <lastmod>'.$lastmod.'</lastmod>';
            $lines[] = '    <changefreq>'.$entry['changefreq'].'</changefreq>';
            $lines[] = '    <priority>'.$entry['priority'].'</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
