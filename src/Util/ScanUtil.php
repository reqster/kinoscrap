<?php

namespace App\Util;

abstract class ScanUtil
{
    public const USER_AGENT_CHROME = 0;
    public const USER_AGENT_DEFAULT = self::USER_AGENT_CHROME;
    public const USER_AGENTS = [self::USER_AGENT_CHROME => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36'];
    public const COOKIE = 'user-geo-region-id=213; user-geo-country-id=2; desktop_session_key=ece9db078b1ee9e2ba6567338dae8ff4adf2e04406744f4964b1fab6c634af8d4e6823d99dd640b00df04be9634648d3987ae7e4aa377495aa6778cf3dcba0df85c01addea8d9b4a16c9fc47b8317e4891732bbbcf3f265cb4beb9c8787596f7; desktop_session_key.sig=t1AAFedIqHbCg5WsHXeUk4EzYpo; _csrf=u27SeVJwphVWx2G-tqQh4pwE; yandex_login=; i=4sTSuOAJM7h+fL0bjN4dkUwRavQZObQbe7l72wjTLQ2sRzEW5xLmX34vk48P8+FYThuh5jXyjxtcB7dxyMhXYd8SMzg=; yandexuid=1485756791673822913; gdpr=0; _ym_uid=1681850138891502813; yuidss=1485756791673822913; _ym_isad=2; yp=1682387303.yu.1485756791673822913; ymex=1684892903.oyu.1485756791673822913; _ym_visorc=b; _yasc=/bLLmxZFudBT3KSV2sj6V7u5ykJDvLXfHxriL3412jMOjiIZzWdLQ9rW9zzCIA==; disable_server_sso_redirect=1; ya_sess_id=noauth:1682311242; ys=c_chck.3357438592; mda2_beacon=1682311242390; sso_status=sso.passport.yandex.ru:synchronized; _ym_d=1682311252; cycada=LoFwSx8Bv3SyK0N+6KwTIn4mxLbCohpIOv7S1zTXmZ8=; spravka=dD0xNjgyMzEyOTM2O2k9MTg4LjEyNC40Ni4xMTc7RD1FRkM5OTY5MTc1RjY2QzU5MEJDREQ5RDY4RDI2NzM3OEI1OUI2MzQ0NzQ3RDQwMTQyMDJBMDk2QjVFMzY3MUNCRUM0RjUzRkI4REE1RkRGOUE0NUU2NzUwMjQwNDY2OURGNkNCNkQ1NkM0NzdDQzt1PTE2ODIzMTI5MzY1NDk4Mjg2NDY7aD04MzU5NmUzNGI3Mjc0YTY3ZjdiNThjNjY5ODcyZmZjMQ==';
    public const PROTOCOL = 'https://';
    public const HOST = 'www.kinopoisk.ru/';
    public const URI = 'lists/movies/top250/';
    public const URL = self::PROTOCOL.self::HOST.self::URI;
    public const PAGE_QUERY = '/?page=';
    public const THROTTLING_TIME = 12; // in seconds

    public const PAGE_MIN = 1; // could change to 0 one day, so better define it
}
