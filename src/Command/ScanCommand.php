<?php

namespace App\Command;

use App\Entity\Movie;
use App\Entity\RatingHistory;
use App\Util\ScanUtil;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use JsonPath\JsonObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

final class ScanCommand extends Command
{
    private const COMMAND_NAME = 'scan';

    private InputInterface $in;

    private OutputInterface $out;

    private Client $client;

    private EntityManagerInterface $entityManager;

    private bool $debugInfo = true;

    private bool $debugDump = true;

    public const ERROR_SUCCESS = 0;
    public const ERROR_HTTP = -1;
    public const ERROR_SCRIPT_NOTFOUND_OR_AMBIGUOUS = -2;
    public const ERROR_EXCEPTION = -3;
    public const ERROR_CONNECTION = -4;
    public const ERROR_CAPTCHA = -5;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct(self::COMMAND_NAME);
        $this->entityManager = $entityManager;
        $this->client = new Client(
            [
                'headers' => [
                    'User-Agent' => ScanUtil::USER_AGENTS[ScanUtil::USER_AGENT_DEFAULT],
                    'Cookie' => ScanUtil::COOKIE,
                ],
            ]
        );
    }

    public function getDebugDump()
    {
        return $this->debugDump;
    }

    public function getDebugInfo()
    {
        return $this->debugInfo;
    }

    public function configure(): void
    {
        $this->setName(self::COMMAND_NAME)->setDescription('Scan command supposed to be run daily to collect movie data.');
    }

    public function execute(InputInterface $in, OutputInterface $out): int
    {
        $out->writeln('Scan command running...');

        $out->writeln('Initialization...');
        $this->in = $in;
        $this->out = $out;

        if ($this->getDebugInfo()) {
            $out->writeln('User-Agent set to "'.$this->client->getConfig('headers')['User-Agent'].'"');
            // $out->writeln('Cookie set to "' . $this->client->getConfig('headers')['Cookie'] . '"');
            $out->writeln('URL set to "'.ScanUtil::URL.'"');
        }

        $out->writeln('Scanning...');

        $response = $this->client->request('GET', ScanUtil::URL);
        if (RESPONSE::HTTP_OK !== $response->getStatusCode()) {
            $out->writeln('Http Client response is not OK!');

            return self::ERROR_HTTP;
        }
        $html = $response->getBody();

        if ($this->getDebugDump()) {
            @file_put_contents('./var/scan.html', $html);
            $out->writeln('Response has been dumped');
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        // Yeah, I COULD put selectors into consts, but practically speaking this is inconvenient to edit later
        $elements = $xpath->query("//a[contains(@href, 'page')]");
        $maxPage = count($elements) - 1;
        $out->writeln('Scanning '.$maxPage.' pages');
        if ($maxPage <= 0) {
            $out->writeln('No pages found. Anti-bot security may have returned a captcha.');

            return self::ERROR_CAPTCHA;
        }

        $movieRepo = $this->entityManager->getRepository(Movie::class);

        try {
            $this->entityManager->getConnection()->beginTransaction();
            for ($pageNum = 1; $pageNum <= $maxPage; ++$pageNum) {
                $out->writeln('Page '.$pageNum);
                sleep(ScanUtil::THROTTLING_TIME);
                $response = $this->client->request('GET', ScanUtil::URL.ScanUtil::PAGE_QUERY.$pageNum);
                if (RESPONSE::HTTP_OK !== $response->getStatusCode()) {
                    $out->writeln('Http Client response is not OK!');

                    return self::ERROR_HTTP;
                }
                $html = $response->getBody();

                @$doc->loadHTML($html);
                $xpath = new \DOMXPath($doc);

                // Accurate rating data exists in a separate node
                $scripts = $xpath->query("*/script[@id='__NEXT_DATA__']/text()[1]");
                if (1 !== count($scripts)) {
                    $out->writeln('Rating data script not found or ambiguous!');

                    return self::ERROR_SCRIPT_NOTFOUND_OR_AMBIGUOUS;
                }
                $ratingScript = $doc->saveHTML($scripts[0]);
                if ($this->getDebugDump()) {
                    @file_put_contents('./var/script.html', $ratingScript);
                    $out->writeln('Rating data script has been dumped');
                }

                $scriptJson = new JsonObject($ratingScript);
                if ($this->getDebugDump()) {
                    @file_put_contents('./var/json.txt', var_export($scriptJson->getValue(), true));
                    $out->writeln('JSON data has been dumped');
                }

                $positionList = $scriptJson->get("$..*[?(@.__typename == 'FilteredMovieList')].items")[0];
                // converting to more PHP-friendly format
                $ratingArray = [];
                foreach ($positionList as $positionItem) {
                    preg_match("/(\d+)/", $positionItem['movie']['__ref'], $matches);
                    if ($matches) {
                        $key = $matches[1];
                        $ratingArray[(int) $key] = ['position' => intval($positionItem['position']), 'rate' => floatval($positionItem['rate']), 'votes' => intval($positionItem['votes'])];
                    }
                }

                // Saving general movie data and rating data
                $movieNodes = $xpath->query("//main//a[contains(@class, 'base-movie-main-info')]");
                foreach ($movieNodes as $movieNode) {
                    $kinopoiskId = $movieNode->getAttribute('href');
                    // Yes, I could use a PREG for that. But using a constant PCRE expression is really no different from using a constant character position
                    $kinopoiskId = (int) substr($kinopoiskId, 6, -1); // Not like the site we're scraping gonna SQL inject us, but still...
                    $movie = $movieRepo->findOneBy(['kinopoiskId' => $kinopoiskId]);

                    // Add a new movie if it doesn't already exist
                    if (!$movie) {
                        if ($this->getDebugInfo()) {
                            $out->writeln("Found new movie $kinopoiskId - adding.");
                        }
                        $movie = new Movie();
                        $movie->setKinopoiskId($kinopoiskId);
                        $titleNodes = $xpath->query(".//span[contains(@class, 'secondaryTitle')]", $movieNode);
                        if (count($titleNodes)) {
                            $title = $titleNodes[0]->nodeValue;
                        } else {
                            $title = $xpath->query(".//span[contains(@class, 'activeMovieTittle')]", $movieNode)[0]->nodeValue;
                        }
                        $movie->setTitle($title);

                        $secondary = $xpath->query(".//span[contains(@class, 'secondaryText')]", $movieNode)[0]->nodeValue;
                        $matches = [];
                        preg_match("/(\d+),/", $secondary, $matches);
                        $year = $matches[1];
                        $releaseDate = new \DateTime("$year-01-01");
                        $movie->setReleaseDate($releaseDate);

                        $this->entityManager->persist($movie);
                    }

                    // Saving rating data
                    $ratingHistory = new RatingHistory();
                    $ratingHistory->setMovie($movie);
                    $ratingHistory->setActiveScrape(true);
                    if (isset($ratingArray[$movie->getKinopoiskId()])) {
                        $ratingHistory->setPosition($ratingArray[$movie->getKinopoiskId()]['position']);
                        $ratingHistory->setVotes($ratingArray[$movie->getKinopoiskId()]['votes']);
                        $ratingHistory->setValue($ratingArray[$movie->getKinopoiskId()]['rate']);
                    }
                    $this->entityManager->persist($ratingHistory);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (ConnectionException $e) {
            // No rollback possible since no connection is present
            $out->writeln('Connection exception occured:');
            $out->writeln('Code: '.$e->getCode());
            $out->writeln('Message: '.$e->getMessage());

            return self::ERROR_CONNECTION;
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $out->writeln('General exception occured:');
            $out->writeln('Code: '.$e->getCode());
            $out->writeln('Message: '.$e->getMessage());

            return self::ERROR_EXCEPTION;
        }

        $out->writeln('Done');

        return self::ERROR_SUCCESS;
    }
}
