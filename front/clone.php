<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

header('Content-Type: application/json; charset=utf-8');

function subticket_respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    subticket_respond(405, ['ok' => false, 'error' => 'Método não permitido']);
}

$id    = (int) ($_POST['id']    ?? 0);
$count = (int) ($_POST['count'] ?? 1);
$count = max(1, min($count, PluginSubticketCloner::MAX_COPIES));

$ticket = new Ticket();

if (
    $id <= 0
    || !$ticket->getFromDB($id)
    || !$ticket->canViewItem()
    || !PluginSubticketCloner::canUse()
) {
    subticket_respond(403, ['ok' => false, 'error' => 'Sem permissão para criar SubChamado a partir deste chamado']);
}

try {
    $ids = [];
    for ($i = 0; $i < $count; $i++) {
        $ids[] = PluginSubticketCloner::cloneTicket($id);
    }
    subticket_respond(200, [
        'ok'    => true,
        'count' => count($ids),
        'ids'   => $ids,
        'url'   => count($ids) === 1
            ? Ticket::getFormURLWithID($ids[0])
            : Ticket::getSearchURL(),
    ]);
} catch (\Throwable $e) {
    error_log('[subticket] ' . $e->getMessage());
    subticket_respond(500, ['ok' => false, 'error' => $e->getMessage()]);
}
