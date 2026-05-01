<?php

declare(strict_types=1);

if (!isset($pdo, $user)) {
    return;
}

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_role_text')) {
    function format_role_text(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $text = preg_replace('/\[(.*?)\]/', '&lt;$1&gt;', $text);
        $text = preg_replace('/\&lt;(.*?)\&gt;/', '<span class="role-action-text">&lt;$1&gt;</span>', $text);

        return nl2br((string) $text);
    }
}

$idUser = (int) $user['id_user'];

$stmt = $pdo->prepare("
    SELECT c.id_character, c.name
    FROM characters c
    JOIN lands l ON l.id_land = c.id_land
    WHERE c.id_user = :id_user
    AND l.slug = 'city'
    LIMIT 1
");
$stmt->execute([
    'id_user' => $idUser,
]);

$roleCharacter = $stmt->fetch();

if (!$roleCharacter) {
    echo '<div class="role-empty">Personaggio city non trovato.</div>';
    return;
}

$idCharacter = (int) $roleCharacter['id_character'];

$selectedMonth = isset($_GET['role_month']) ? (string) $_GET['role_month'] : date('Y-m');
$selectedSessionId = isset($_GET['role']) ? (int) $_GET['role'] : 0;

if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

$monthStart = $selectedMonth . '-01 00:00:00';
$monthEnd = date('Y-m-d H:i:s', strtotime($monthStart . ' +1 month'));

$stmt = $pdo->prepare("
    SELECT DISTINCT DATE_FORMAT(started_at, '%Y-%m') AS role_month
    FROM city_chat_sessions
    WHERE id_user = :id_user
    AND id_character = :id_character
    ORDER BY role_month DESC
");
$stmt->execute([
    'id_user' => $idUser,
    'id_character' => $idCharacter,
]);

$availableMonths = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($availableMonths) && !in_array($selectedMonth, $availableMonths, true)) {
    $selectedMonth = (string) $availableMonths[0];
    $monthStart = $selectedMonth . '-01 00:00:00';
    $monthEnd = date('Y-m-d H:i:s', strtotime($monthStart . ' +1 month'));
}

$selectedSession = null;
$selectedMessages = [];
$selectedPresentText = '';
$selectedActionCount = 0;

if ($selectedSessionId > 0) {
    $stmt = $pdo->prepare("
        SELECT
            s.id_session,
            s.id_user,
            s.id_character,
            s.id_chat,
            s.started_at,
            s.ended_at,
            ch.name AS chat_name
        FROM city_chat_sessions s
        JOIN city_chats ch ON ch.id_chat = s.id_chat
        WHERE s.id_user = :id_user
        AND s.id_character = :id_character
        AND s.id_session = :id_session
        LIMIT 1
    ");
    $stmt->execute([
        'id_user' => $idUser,
        'id_character' => $idCharacter,
        'id_session' => $selectedSessionId,
    ]);

    $selectedSession = $stmt->fetch() ?: null;

    if ($selectedSession) {
        $startedAt = (string) $selectedSession['started_at'];
        $endedAt = $selectedSession['ended_at'] !== null ? (string) $selectedSession['ended_at'] : null;
        $endForQuery = $endedAt ?? date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            SELECT
                m.id_message,
                m.content,
                m.created_at,
                m.message_type,
                m.context,
                c.name AS author
            FROM city_chat_messages m
            JOIN characters c ON c.id_character = m.id_character
            WHERE m.id_chat = :id_chat
            AND m.created_at >= :started_at
            AND m.created_at <= :ended_at
            ORDER BY m.created_at ASC, m.id_message ASC
        ");
        $stmt->execute([
            'id_chat' => (int) $selectedSession['id_chat'],
            'started_at' => $startedAt,
            'ended_at' => $endForQuery,
        ]);

        $selectedMessages = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT DISTINCT c.name
            FROM city_chat_messages m
            JOIN characters c ON c.id_character = m.id_character
            WHERE m.id_chat = :id_chat
            AND m.created_at >= :started_at
            AND m.created_at <= :ended_at
            ORDER BY c.name ASC
        ");
        $stmt->execute([
            'id_chat' => (int) $selectedSession['id_chat'],
            'started_at' => $startedAt,
            'ended_at' => $endForQuery,
        ]);

        $presentNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $selectedPresentText = !empty($presentNames) ? implode(', ', $presentNames) : 'Nessun presente registrato';

        foreach ($selectedMessages as $messageCounter) {
            if ((string) $messageCounter['message_type'] === 'azione') {
                $selectedActionCount++;
            }
        }
    }
}

$stmt = $pdo->prepare("
    SELECT
        s.id_session,
        s.id_chat,
        s.started_at,
        s.ended_at,
        ch.name AS chat_name,
        (
            SELECT COUNT(*)
            FROM city_chat_messages m
            WHERE m.id_chat = s.id_chat
            AND m.message_type = 'azione'
            AND m.created_at >= s.started_at
            AND m.created_at <= COALESCE(s.ended_at, NOW())
        ) AS action_count
    FROM city_chat_sessions s
    JOIN city_chats ch ON ch.id_chat = s.id_chat
    WHERE s.id_user = :id_user
    AND s.id_character = :id_character
    AND s.started_at >= :month_start
    AND s.started_at < :month_end
    ORDER BY s.started_at DESC
");
$stmt->execute([
    'id_user' => $idUser,
    'id_character' => $idCharacter,
    'month_start' => $monthStart,
    'month_end' => $monthEnd,
]);

$sessions = $stmt->fetchAll();

$monthNames = [
    '01' => 'gennaio',
    '02' => 'febbraio',
    '03' => 'marzo',
    '04' => 'aprile',
    '05' => 'maggio',
    '06' => 'giugno',
    '07' => 'luglio',
    '08' => 'agosto',
    '09' => 'settembre',
    '10' => 'ottobre',
    '11' => 'novembre',
    '12' => 'dicembre',
];

function role_month_label(string $value, array $monthNames): string
{
    $parts = explode('-', $value);

    if (count($parts) !== 2) {
        return $value;
    }

    $year = $parts[0];
    $month = $parts[1];

    return ($monthNames[$month] ?? $month) . ' ' . $year;
}

?>

<link rel="stylesheet" href="/themes/my_roles.css?v=my-roles-03">

<div class="my-roles-container">

    <form class="role-filter" method="get" action="/index.php">
        <input type="hidden" name="page" value="my_roles">

        <label class="role-filter-label" for="role-month">Mese</label>

        <select id="role-month" name="role_month" class="role-filter-select" onchange="this.form.submit()">
            <?php if (empty($availableMonths)) { ?>
                <option value="<?php echo e(date('Y-m')); ?>"><?php echo e(role_month_label(date('Y-m'), $monthNames)); ?></option>
            <?php } ?>

            <?php foreach ($availableMonths as $monthValue) { ?>
                <option value="<?php echo e((string) $monthValue); ?>" <?php echo $selectedMonth === $monthValue ? 'selected' : ''; ?>>
                    <?php echo e(role_month_label((string) $monthValue, $monthNames)); ?>
                </option>
            <?php } ?>
        </select>
    </form>

    <?php if ($selectedSession) { ?>

        <?php
            $selectedStartedAt = (string) $selectedSession['started_at'];
            $selectedEndedAt = $selectedSession['ended_at'] !== null ? (string) $selectedSession['ended_at'] : null;
        ?>

        <div class="role-opened">

            <div class="role-opened-header">
                <a class="role-back-link" href="/index.php?page=my_roles&role_month=<?php echo e(date('Y-m', strtotime($selectedStartedAt))); ?>">
                    ← Torna alla lista
                </a>

                <div class="role-opened-title">
                    <?php echo e(date('d/m/Y', strtotime($selectedStartedAt))); ?>
                    —
                    <?php echo e((string) $selectedSession['chat_name']); ?>
                </div>

                <div class="role-opened-meta">
                    <span><?php echo e(date('H:i', strtotime($selectedStartedAt))); ?> → <?php echo $selectedEndedAt !== null ? e(date('H:i', strtotime($selectedEndedAt))) : 'in corso'; ?></span>
                    <span><?php echo e((string) $selectedActionCount); ?> azioni</span>
                    <span><?php echo e($selectedPresentText); ?></span>
                </div>
            </div>

            <div class="role-messages">

                <?php if (empty($selectedMessages)) { ?>
                    <div class="role-empty">Nessun messaggio in questa role.</div>
                <?php } ?>

                <?php foreach ($selectedMessages as $msg) { ?>

                    <?php
                        $messageType = (string) $msg['message_type'];
                        $context = (string) ($msg['context'] ?? '');
                    ?>

                    <div class="role-message role-message-<?php echo e($messageType); ?>">
                        <span class="role-time">[<?php echo e(date('H:i', strtotime((string) $msg['created_at']))); ?>]</span>
                        <span class="role-author"><?php echo e((string) $msg['author']); ?></span>

                        <?php if ($context !== '') { ?>
                            <span class="role-context">[<?php echo e($context); ?>]</span>
                        <?php } ?>

                        <span class="role-text"><?php echo format_role_text((string) $msg['content']); ?></span>
                    </div>

                <?php } ?>

            </div>

        </div>

    <?php } else { ?>

        <?php if (empty($sessions)) { ?>

            <div class="role-empty">Nessuna role trovata per questo mese.</div>

        <?php } else { ?>

            <table class="role-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Chat</th>
                        <th>Orario</th>
                        <th>Azioni</th>
                        <th>Apri</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($sessions as $session) { ?>

                        <?php
                            $startedAt = (string) $session['started_at'];
                            $endedAt = $session['ended_at'] !== null ? (string) $session['ended_at'] : null;
                        ?>

                        <tr>
                            <td><?php echo e(date('d/m/Y', strtotime($startedAt))); ?></td>
                            <td><?php echo e((string) $session['chat_name']); ?></td>
                            <td>
                                <?php echo e(date('H:i', strtotime($startedAt))); ?>
                                →
                                <?php echo $endedAt !== null ? e(date('H:i', strtotime($endedAt))) : 'in corso'; ?>
                            </td>
                            <td><?php echo e((string) $session['action_count']); ?></td>
                            <td>
                                <a
                                    class="role-open-link"
                                    href="/index.php?page=my_roles&role_month=<?php echo e(date('Y-m', strtotime($startedAt))); ?>&role=<?php echo (int) $session['id_session']; ?>"
                                >
                                    Apri
                                </a>
                            </td>
                        </tr>

                    <?php } ?>
                </tbody>
            </table>

        <?php } ?>

    <?php } ?>

</div>

<?php
// by LaEmiX