<?php

declare(strict_types=1);

use App\Ntfy;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client;

require __DIR__ . '/vendor/autoload.php';

$world = $_SERVER['WORLD'] ?? null;
$username = $_SERVER['USERNAME'] ?? null;
$password = $_SERVER['PASSWORD'] ?? null;
$ntfyTopic = $_SERVER['NTFY_TOPIC'] ?? null;

if ($world === null || $username === null || $password === null || $ntfyTopic === null) {
    echo 'Error: You need to run the command like this and fill in your own values "docker run --env USERNAME=Sotrax --env PASSWORD=Password123 --env WORLD=welt1 --env NTFY_TOPIC=my-cool-topic freewar-oil-warehouse"' . PHP_EOL;

    exit(1);
}

$ntfy = new Ntfy($ntfyTopic);

$run = false;
while ($run === false) {
    if (rand(0, 30) === 0) {
        $run = true;
    } else {
        sleep(30 + rand(0, 30));
    }
}

try {
    $client = Client::createChromeClient(null, [
        '--no-sandbox',
        '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
        '--window-size=1200,1100',
        '--headless',
        '--disable-gpu',
        '--disable-dev-shm-usage',
    ]);

    $client->request('GET', sprintf('https://%s.freewar.de/freewar/', $world));

    $client->findElement(WebDriverBy::cssSelector('input[name="name"]'))->sendKeys($username);
    $client->findElement(WebDriverBy::cssSelector('input[name="password"]'))->sendKeys($password);
    $client->findElement(WebDriverBy::cssSelector('input[name="submit"]'))->click();

    $client->request('GET', sprintf('https://%s.freewar.de/freewar/internal/main.php', $world));
    sleep(rand(2, 5));
    $field = trim($client->findElement(WebDriverBy::cssSelector('td.mainheader'))->getText());

    if ($field !== 'Mentoran - Das Öl-Lagerhaus') {
        $ntfy->sendErrorMessage(sprintf('Du stehst mit deinem User "%s" in Welt "%s" auf Feld "%s"!', $username, $world, $field));
        sleep(rand(2, 5));
        $client->request('GET', sprintf('https://%s.freewar.de/freewar/internal/logout.php', $world));

        exit(1);
    }

    sleep(rand(2, 5));
    $fieldText = trim($client->findElement(WebDriverBy::cssSelector('td.areadescription'))->getText());

    if (!str_contains($fieldText, " mitnehmen")) {
        $ntfy->sendErrorMessage(sprintf('Keine Ölfässer zum mitnehmen vorhanden bei deinem User "%s" in Welt "%s" auf Feld "%s"!', $username, $world, $field));
        sleep(rand(2, 5));
        $client->request('GET', sprintf('https://%s.freewar.de/freewar/internal/logout.php', $world));

        exit(1);
    }

    $takeBarrelsLinkText = $client->findElement(WebDriverBy::cssSelector('td.areadescription a'))->getText();
    $client->findElement(WebDriverBy::cssSelector('td.areadescription a'))->click();
    sleep(rand(2, 5));
    $ntfy->sendSuccessMessage(sprintf('Du hast erfolgreich %s Fässer Öl mit deinem User "%s" in Welt "%s" mitgenommen!', str_replace(' Ölfässer mitnehmen.', '', $takeBarrelsLinkText), $username, $world));
    sleep(rand(2, 5));
    $client->request('GET', sprintf('https://%s.freewar.de/freewar/internal/logout.php', $world));
} catch (Throwable $e) {
    $ntfy->sendErrorMessage($e->getMessage());

    exit(1);
}

exit(0);
