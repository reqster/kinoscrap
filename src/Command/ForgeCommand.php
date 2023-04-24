<?php

namespace App\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ForgeCommand extends Command
{
    private InputInterface $in;

    private OutputInterface $out;

    private Client $client;

    public function __construct()
    {
        parent::__construct('forge');
        $this->client = new Client(
            [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36',
                    'Cookie' => 'user-geo-region-id=213; user-geo-country-id=2; desktop_session_key=ece9db078b1ee9e2ba6567338dae8ff4adf2e04406744f4964b1fab6c634af8d4e6823d99dd640b00df04be9634648d3987ae7e4aa377495aa6778cf3dcba0df85c01addea8d9b4a16c9fc47b8317e4891732bbbcf3f265cb4beb9c8787596f7; desktop_session_key.sig=t1AAFedIqHbCg5WsHXeUk4EzYpo; _csrf=u27SeVJwphVWx2G-tqQh4pwE; disable_server_sso_redirect=1; _yasc=iJqdk5XJVrJldgxueE/mJK8Zzsh5ZVkuONpSqS5ldwYqFzZx7IrNtxSUJVY=; ya_sess_id=noauth:1681860030; yandex_login=; ys=c_chck.1489380467; i=4sTSuOAJM7h+fL0bjN4dkUwRavQZObQbe7l72wjTLQ2sRzEW5xLmX34vk48P8+FYThuh5jXyjxtcB7dxyMhXYd8SMzg=; yandexuid=1485756791673822913; mda2_beacon=1681860030532; sso_status=sso.passport.yandex.ru:synchronized; gdpr=0; _ym_uid=1681850138891502813; _ym_isad=2; _ym_visorc=b; spravka=dD0xNjgxODYwMDQxO2k9MTg4LjEyNC40Ni4xMTc7RD04QkRFQzlBOEZEQzY4RDM1MDhDQjNEN0RDNkI2MDY2NzgzRjk2QjYxRTkzMTMwRUExNTZDRDdGN0UyM0ZDMkZDMEJFRjNCMjQ4RjU2NTVDOEU2REUwMEY1OUZEOTAwOTI7dT0xNjgxODYwMDQxMTA5NTg4MjI4O2g9OTc4NDZhNjFlMWYyYTJiNTJmYzlhMGQwYTVhY2M5ZDM=; _ym_d=1681860042; cycada=q3jMwryiZNNn56pce51LTH4mxLbCohpIOv7S1zTXmZ8=',
                ],
            ]
        );
    }

    public function configure(): void
    {
        $this->setName('forge')->setDescription('Forge command for all around general testing while developing. Not supposed to be used when the task solution is evaluated.');
    }

    public function execute(InputInterface $in, OutputInterface $out): int
    {
        $this->in = $in;
        $this->out = $out;
        $doc = new \DOMDocument();

        $out->writeln('Running the forge...');

        // $response = $this->client->request('GET', 'https://api.github.com/repos/guzzle/guzzle');
        $response = $this->client->request('GET', 'https://www.kinopoisk.ru/lists/movies/top250/');
        $out->writeln($response->getStatusCode());
        $html = $response->getBody();
        @file_put_contents('./var/forge.html', $html);
        $out->writeln('File dumped');
        $domHtml = @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);
        $elements = $xpath->query("*/script[@id='__NEXT_DATA__']");
        $scriptDump = '';
        foreach ($elements as $element) {
            $scriptDump .= $doc->saveHTML($element);
        }
        @file_put_contents('./var/script.html', $scriptDump);
        $out->writeln('Script dumped');
        $out->writeln('Done');

        return 0;
    }
}
