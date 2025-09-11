<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'src/middleware.php';

final class MiddlewareTest extends TestCase {
    public function testIsWPSite(): void {
        $tests = array(
            array('url' => '/home', 'result' => false),
            array('url' => '/foo/bar', 'result' => false),
            array('url' => '/wp-admin', 'result' => true),
            array('url' => '/wp-admin/', 'result' => true),
            array('url' => '/wp-admin/tools.php', 'result' => true),
        );

        foreach ($tests as $t) {
            $_SERVER['REQUEST_URI'] = $t['url'];
            $this->assertSame($t['result'], pirsch_analytics_is_wp_site(), $t['url']);
        }
    }

    public function testIsExcluded(): void {
        function get_option($option) {
            return '/foo\n/bar\n'.PIRSCH_FILTER_REGEX_PREFIX.'^\/filter\/page\/.*$';
        }

        $tests = array(
            array('url' => '/home', 'result' => false),
            array('url' => '/foo/bar', 'result' => false),
            array('url' => '/foo', 'result' => true),
            array('url' => '/bar', 'result' => true),
            array('url' => '/filter/page/here', 'result' => true),
        );

        foreach ($tests as $t) {
            $_SERVER['REQUEST_URI'] = $t['url'];
            $this->assertSame($t['result'], pirsch_analytics_is_excluded(), $t['url']);
        }
    }

    public function testParseXForwardedFor(): void {
        $tests = array(
            array('header' => '203.0.113.195', 'result' => '203.0.113.195'),
            array('header' => '203.0.113.195, 70.41.3.18, 150.172.238.178', 'result' => '203.0.113.195'),
            array('header' => ' 203.0.113.195, 70.41.3.18, 150.172.238.178', 'result' => '203.0.113.195'),
        );

        foreach ($tests as $t) {
            $this->assertSame($t['result'], pirsch_analytics_parse_x_forwarded_for($t['header']), $t['header']);
        }
    }

    public function testParseForwarded(): void {
        $tests = array(
            array('header' => 'For="[2001:db8:cafe::17]:4711";By=203.0.113.43', 'result' => '203.0.113.43'),
            array('header' => 'for=192.0.2.60;proto=http;by="203.0.113.43"', 'result' => '203.0.113.43'),
            array('header' => ' for=192.0.2.43, for=198.51.100.17;by=203.0.113.43', 'result' => '203.0.113.43'),
        );

        foreach ($tests as $t) {
            $this->assertSame($t['result'], pirsch_analytics_parse_forwarded_header($t['header']), $t['header']);
        }
    }
}
