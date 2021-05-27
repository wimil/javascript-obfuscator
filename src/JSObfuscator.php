<?php

namespace Wimil;

class JSObfuscator {
    private $code;
    private $mask;
    private $interval;
    private $option = 0;
    private $expireTime = 0;
    private $domainNames = [];

    function __construct($Code, $html = false) {
        ($html and ($this->code = $this->_html2Js($this->_cleanHtml($Code)))) or ($this->code = $this->_cleanJS($Code));

        $this->mask = $this->_getMask();
        $this->interval = rand(1, 50);
        $this->option = rand(2, 8);
    }

    private function _getMask() {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 9);
    }

    private function _hashIt($s) {
        for ($i = 0; $i < strlen($this->mask); ++$i)
            $s = str_replace("$i", $this->mask[$i], $s);
        return $s;
    }

    private function _prepare() {
        if (count($this->domainNames) > 0) {
            $code = "if(window.location.hostname==='" . $this->domainNames[0] . "' ";
            for ($i = 1; $i < count($this->domainNames); $i++)
                $code .= "|| window.location.hostname==='" . $this->domainNames[$i] . "' ";
            $this->code = $code . "){" . $this->code . "}";
        }
        if ($this->expireTime > 0)
            $this->code = 'if((Math.round(+new Date()/1000)) < ' . $this->expireTime . '){' . $this->code . '}';
    }

    private function _encodeIt() {
        $this->_prepare();
        $str = "";
        for ($i = 0; $i < strlen($this->code); ++$i)
            $str .= $this->_hashIt(base_convert(ord($this->code[$i]) + $this->interval, 10, $this->option)) . $this->mask[$this->option];
        return $str;
    }

    public function Obfuscate() {
        $rand = rand(0,99);
        $rand1 = rand(0,99);
        $encoded = $this->_encodeIt();
        $rand2 = rand(1, 100);
        $rand3 = rand(1, 60);
        return "let _0xc{$rand}e=[\"\",\"\x73\x70\x6C\x69\x74\",\"\x30\x31\x32\x33\x34\x35\x36\x37\x38\x39\x61\x62\x63\x64\x65\x66\x67\x68\x69\x6A\x6B\x6C\x6D\x6E\x6F\x70\x71\x72\x73\x74\x75\x76\x77\x78\x79\x7A\x41\x42\x43\x44\x45\x46\x47\x48\x49\x4A\x4B\x4C\x4D\x4E\x4F\x50\x51\x52\x53\x54\x55\x56\x57\x58\x59\x5A\x2B\x2F\",\"\x73\x6C\x69\x63\x65\",\"\x69\x6E\x64\x65\x78\x4F\x66\",\"\",\"\",\"\x2E\",\"\x70\x6F\x77\",\"\x72\x65\x64\x75\x63\x65\",\"\x72\x65\x76\x65\x72\x73\x65\",\"\x30\"];function _0xe{$rand1}c(d,e,f){let g=_0xc{$rand}e[2][_0xc{$rand}e[1]](_0xc{$rand}e[0]);let h=g[_0xc{$rand}e[3]](0,e);let i=g[_0xc{$rand}e[3]](0,f);let j=d[_0xc{$rand}e[1]](_0xc{$rand}e[0])[_0xc{$rand}e[10]]()[_0xc{$rand}e[9]](function(a,b,c){if(h[_0xc{$rand}e[4]](b)!==-1)return a+=h[_0xc{$rand}e[4]](b)*(Math[_0xc{$rand}e[8]](e,c));},0);let k=_0xc{$rand}e[0];while(j>0){k=i[j%f]+k;j=(j-(j%f))/f;}return k||_0xc{$rand}e[11];}eval((function (r, a, n, t, e, s) { let x='',len=r.length,i=0,j=0;s='';for(i=0;i<len;i++){x='';while(r[i]!==n[e]){x+=r[i];i++;} for(j=0;j<n.length;j++) x=x.replace(new RegExp(n[j],'g'),j); s+=String.fromCharCode(_0xe{$rand1}c(x,e,10)-t);} return decodeURIComponent(escape(s));})('{$encoded}',{$rand2},'{$this->mask}',{$this->interval},{$this->option},{$rand3}));";
    }

    public function setExpiration($expireTime) {
        if (strtotime($expireTime)) {
            $this->expireTime = strtotime($expireTime);
            return true;
        }
        return false;
    }

    public function addDomainName($domainName) {
        if ($this->_isValidDomain($domainName)) {
            $this->domainNames[] = $domainName;
            return true;
        }
        return false;
    }

    private function _isValidDomain($domain_name) {
        return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name)
            && preg_match("/^.{1,253}$/", $domain_name)
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name));
    }

    private function _html2Js($code) {
        $search = [
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        ];
        $replace = [
            '>',
            '<',
            '\\1',
            ''
        ];
        $code = preg_replace($search, $replace, $code);
        $code = "document.write('" . addslashes($code . " ") . "');";
        return $code;
    }

    private function _cleanHtml($code) {
        return preg_replace('/<!--(.|\s)*?-->/', '', $code);
    }

    private function _cleanJS($code) {
        $pattern = '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/';
        $code = preg_replace($pattern, '', $code);
        $search = [
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
           '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        ];
        $replace = [
            '>',
            '<',
           '\\1',
            ''
        ];
        return preg_replace($search, $replace, $code);
    }
}
