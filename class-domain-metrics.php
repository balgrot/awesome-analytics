<?php

class DomainMetrics
{
    const TIMEOUT = 10;
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36';

    const CACHE_EXPIRATION = 86400;
    const CACHE_KEY = 'awesome-analytics-domain-metrics';

    private $domain;

    public function __construct($domain)
    {
        $this->domain = str_replace( array( 'http://', 'https://', 'www.' ), '', $domain );
    }

    private function getGoogleIndexedPages()
    {
        $data = [
            'hl' => 'en',
            'q' => 'site:' . $this->domain,
        ];
        $url = 'https://www.google.com/search?' . http_build_query($data);;
        $args = [
            'timeout' => self::TIMEOUT,
            'user-agent' => self::USER_AGENT,
        ];
        $response = wp_remote_get($url, $args);
        if (is_array($response) && !is_wp_error($response)) {
            $response = str_replace('&nbsp;', ' ', $response['body']);
            if (preg_match('#([0-9,\.]+) results#i', $response, $p)) {
                return number_format((int)str_replace(array(',', '.'), '', $p[1]));
            }
        }

        return 0;
    }

    private function getBingIndexedPages()
    {
        $data = [
            'setlang' => 'en-US',
            'rdr' => '1',
            'q' => 'site:' . $this->domain,
        ];
        $url = 'https://www.bing.com/search?' . http_build_query($data);
        $args = [
            'timeout' => self::TIMEOUT,
            'user-agent' => self::USER_AGENT,
        ];
        $response = wp_remote_get($url, $args);
        if (is_array($response) && !is_wp_error($response)) {
            $response = str_replace('&nbsp;', ' ', $response['body']);
            $response = str_replace('&#160;', '', $response);

            if (preg_match('#([0-9,\.]+) results#i', $response, $p)) {
                return number_format((int)str_replace(array(',', '.'), '', $p[1]));
            }
        }

        return 0;
    }

    private function getAlexaPageRank()
    {
        $url = 'https://www.alexa.com/minisiteinfo/' . urlencode($this->domain);
        $args = [
            'timeout' => self::TIMEOUT,
            'user-agent' => self::USER_AGENT,
        ];
        $response = wp_remote_get($url, $args);
        if (is_array($response) && !is_wp_error($response)) {
            $dom = new \DomDocument();
            @$dom->loadHTML($response['body']);
            $nodes = (new \DomXPath($dom))->query("//div[contains(@class, 'data')]");
            if (isset($nodes[0]->nodeValue)) {
                return number_format((int)str_replace(array(',', '.'), '', $nodes[0]->nodeValue));
            }
        }

        return 0;
    }

    private function getDomainAge()
    {
        $ages = array();

        $age = $this->getArchiveOrgDomainAge();
        if ($age > 0) {
            $ages[] = $age;
        }

        $age = $this->getWhoIsDomainAge();
        if ($age > 0) {
            $ages[] = $age;
        }

        $age = $this->getWhoisComDomainAge();
        if ($age > 0) {
            $ages[] = $age;
        }

        if (count($ages) > 0) {
            $value = min($ages);
            return $this->getPrettyTimeFromSeconds(time() - $value);
        }

        return 0;
    }

    private function getArchiveOrgDomainAge()
    {
        $url = 'https://archive.org/wayback/available?timestamp=19900101&url=' . urlencode($this->domain);
        $args = [
            'timeout' => self::TIMEOUT,
            'user-agent' => self::USER_AGENT,
        ];
        $response = wp_remote_get($url, $args);
        if (is_array($response) && !is_wp_error($response)) {
            $data = json_decode($response['body'], true);
            if (empty($data['archived_snapshots']['closest']['timestamp'])) {
                return 0;
            }
            return strtotime($data['archived_snapshots']['closest']['timestamp']);
        }

        return 0;
    }

    private function getWhoIsDomainAge()
    {
        $url = 'https://www.who.is/whois/' . urlencode($this->domain);
        $args = [
            'timeout' => self::TIMEOUT,
            'user-agent' => self::USER_AGENT,
        ];
        $response = wp_remote_get($url, $args);
        if (is_array($response) && !is_wp_error($response)) {
            preg_match('#(?:Creation Date|Created On|created|Registered on)\.*:\s*([ \ta-z0-9\/\-:\.]+)#si', $response['body'], $p);
            if (!empty($p[1])) {
                $value = strtotime(trim($p[1]));
                if ($value === false) {
                    return 0;
                }
                return $value;
            }
        }

        return 0;
    }

    private function getWhoisComDomainAge()
    {
        $url = 'https://www.whois.com/whois/' . urlencode($this->domain);
        $args = [
            'timeout' => self::TIMEOUT,
            'user-agent' => self::USER_AGENT,
        ];
        $response = wp_remote_get($url, $args);
        if (is_array($response) && !is_wp_error($response)) {
            preg_match('#(?:Creation Date|Created On|created|Registration Date):\s*([ \ta-z0-9\/\-:\.]+)#si', $response['body'], $p);
            if (!empty($p[1])) {
                $value = strtotime(trim($p[1]));
                if ($value === false) {
                    return 0;
                }
                return $value;
            }
            return 0;
        }

        return 0;
    }

    public function getMetrics()
    {

        if ( false === ( $metrics = get_transient( self::CACHE_KEY ) ) ) {

            $metrics = [
                'domain' => $this->domain,
                'google_indexed_pages' => $this->getGoogleIndexedPages(),
                'bing_indexed_pages' => $this->getBingIndexedPages(),
                'alexa_page_rank' => $this->getAlexaPageRank(),
                'alexa_url' => 'https://www.alexa.com/siteinfo/' . urlencode($this->domain),
                'domain_age' => $this->getDomainAge(),
            ];

            set_transient(self::CACHE_KEY, $metrics, self::CACHE_EXPIRATION);
        }
        $metrics = get_transient(self::CACHE_KEY);
        return $metrics;
    }

    private function getPrettyTimeFromSeconds($number_of_seconds)
    {
        $number_of_seconds = (float)$number_of_seconds;

        $is_negative = false;
        if ($number_of_seconds < 0) {
            $number_of_seconds = -1 * $number_of_seconds;
            $is_negative = true;
        }

        $seconds_in_year = 86400 * 365.25;

        $years = floor($number_of_seconds / $seconds_in_year);
        $minus_years = $number_of_seconds - $years * $seconds_in_year;
        $days = floor($minus_years / 86400);

        $minus_days = $number_of_seconds - $days * 86400;
        $hours = floor($minus_days / 3600);

        $minus_days_and_hours = $minus_days - $hours * 3600;
        $minutes = floor($minus_days_and_hours / 60);

        $seconds = $minus_days_and_hours - $minutes * 60;
        $precision = ($seconds > 0 && $seconds < 0.01 ? 3 : 2);
        $seconds = number_format(round($seconds, $precision), $precision);

        $trans = [
            'years_and_days' => '%1$s years %2$s days',
            'days_and_hours' => '%1$s days %2$s hours',
            'hours_and_minutes' => '%1$s hours %2$s min',
            'minutes_and_seconds' => '%1$s min %2$ss',
            'seconds' => '%ss',
        ];

        if ($years > 0) {
            $return = sprintf($trans['years_and_days'], $years, $days);
        } elseif ($days > 0) {
            $return = sprintf($trans['days_and_hours'], $days, $hours);
        } elseif ($hours > 0) {
            $return = sprintf($trans['hours_and_minutes'], $hours, $minutes);
        } elseif ($minutes > 0) {
            $return = sprintf($trans['minutes_and_seconds'], $minutes, $seconds);
        } else {
            $return = sprintf($trans['seconds'], $seconds);
        }

        if ($is_negative) {
            $return = '-' . $return;
        }

        return $return;
    }

}