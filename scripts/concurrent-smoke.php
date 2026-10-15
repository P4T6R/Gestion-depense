<?php

/**
 * Concurrent authenticated smoke test.
 *
 * LOAD_TEST_USERS_JSON='[{"email":"user@example.com","password":"..."}]'
 * php scripts/concurrent-smoke.php http://127.0.0.1:8000 3
 */

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$copies = max(1, min(20, (int) ($argv[2] ?? 1)));
$users = json_decode((string) getenv('LOAD_TEST_USERS_JSON'), true);

if (! is_array($users) || $users === []) {
    fwrite(STDERR, "LOAD_TEST_USERS_JSON doit contenir au moins un compte de test.\n");
    exit(2);
}

$clients = [];
for ($copy = 0; $copy < $copies; $copy++) {
    foreach ($users as $user) {
        if (! isset($user['email'], $user['password'])) {
            fwrite(STDERR, "Chaque compte doit contenir email et password.\n");
            exit(2);
        }

        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_COOKIEFILE => '',
            CURLOPT_HTTPHEADER => ['Accept: text/html'],
        ]);
        $clients[] = [
            'handle' => $handle,
            'email' => $user['email'],
            'password' => $user['password'],
            'started_at' => microtime(true),
            'ok' => true,
            'error' => null,
        ];
    }
}

$run = static function (array &$clients): void {
    $multi = curl_multi_init();
    foreach ($clients as &$client) {
        curl_multi_add_handle($multi, $client['handle']);
    }
    unset($client);

    do {
        $status = curl_multi_exec($multi, $running);
        if ($running) {
            curl_multi_select($multi, 1.0);
        }
    } while ($running && $status === CURLM_OK);

    foreach ($clients as &$client) {
        $client['body'] = curl_multi_getcontent($client['handle']);
        $client['http_code'] = curl_getinfo($client['handle'], CURLINFO_HTTP_CODE);
        $client['url'] = curl_getinfo($client['handle'], CURLINFO_EFFECTIVE_URL);
        if (curl_errno($client['handle'])) {
            $client['ok'] = false;
            $client['error'] = curl_error($client['handle']);
        }
        curl_multi_remove_handle($multi, $client['handle']);
    }
    unset($client);
    curl_multi_close($multi);
};

foreach ($clients as &$client) {
    curl_setopt_array($client['handle'], [CURLOPT_URL => $baseUrl.'/login', CURLOPT_HTTPGET => true]);
}
unset($client);
$run($clients);

foreach ($clients as &$client) {
    preg_match('/name="_token"\s+value="([^"]+)"/', (string) $client['body'], $matches);
    if (! isset($matches[1])) {
        $client['ok'] = false;
        $client['error'] = 'Jeton CSRF introuvable';
        continue;
    }
    curl_setopt_array($client['handle'], [
        CURLOPT_URL => $baseUrl.'/login',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_token' => html_entity_decode($matches[1], ENT_QUOTES),
            'email' => $client['email'],
            'password' => $client['password'],
        ]),
    ]);
}
unset($client);
$run($clients);

foreach ($clients as &$client) {
    if (str_contains((string) $client['url'], '/login') || $client['http_code'] !== 200) {
        $client['ok'] = false;
        $client['error'] ??= 'Connexion refusée';
    }
    curl_setopt_array($client['handle'], [
        CURLOPT_URL => $baseUrl.'/dashboard',
        CURLOPT_HTTPGET => true,
        CURLOPT_POST => false,
    ]);
}
unset($client);
$run($clients);

$durations = [];
foreach ($clients as &$client) {
    $client['duration_ms'] = (int) round((microtime(true) - $client['started_at']) * 1000);
    $durations[] = $client['duration_ms'];
    if ($client['http_code'] !== 200 || str_contains((string) $client['url'], '/login')) {
        $client['ok'] = false;
        $client['error'] ??= 'Session non authentifiée';
    }
    curl_close($client['handle']);
    unset($client['handle'], $client['password'], $client['body'], $client['started_at']);
}
unset($client);

sort($durations);
$successes = count(array_filter($clients, fn ($client) => $client['ok']));
$p95Index = max(0, min(count($durations) - 1, (int) ceil(count($durations) * 0.95) - 1));
$summary = [
    'success' => $successes,
    'total' => count($clients),
    'average_ms' => (int) round(array_sum($durations) / count($durations)),
    'p95_ms' => $durations[$p95Index],
    'max_ms' => max($durations),
    'clients' => $clients,
];

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($successes === count($clients) ? 0 : 1);
