<?php

namespace LaPoste\Colissimo\Model\Carrier
{

    function curl_exec($ch)
    {
        $body = <<<'END_BODY'
--uuid:115fc49e-f2cc-4ab0-9971-8db0a7959753
Content-Type: application/json;charset=UTF-8
Content-Transfer-Encoding: binary
Content-ID: <jsonInfos>

{"success":"some-json"}
--uuid:115fc49e-f2cc-4ab0-9971-8db0a7959753
Content-Type: application/octet-stream
Content-Transfer-Encoding: binary
Content-ID: <label>

some-binary-content

--uuid:115fc49e-f2cc-4ab0-9971-8db0a7959753--
END_BODY;

        $body = str_replace("\n", "\r\n", $body); // these are the body response "normal" ends of line

        return $body;
    }

    function curl_getinfo($ch, $kind)
    {
        return 200;
    }
}
