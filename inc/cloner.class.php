<?php

/**
 * Plugin subticket — classe principal.
 * Registra uma aba lateral no formulário do chamado e implementa a cópia completa.
 */
class PluginSubticketCloner extends CommonGLPI
{
    public static $rightname = 'ticket';

    /**
     * Status com que o clone nasce.
     *  Número = status fixo (ex.: 2 "Em atendimento" no AGETIC;
     *          Ticket::INCOMING, SOLVED, CLOSED etc. em instâncias padrão).
     *  null   = preserva o status do chamado original.
     *
     * No AGETIC o status "Novo" (1) não é válido na UI, por isso o padrão é 2.
     */
    public const CLONE_STATUS = 2;

    /** Teto de SubChamados por operação. */
    public const MAX_COPIES = 20;

    /** Prefixo do título dos chamados criados. */
    public const TITLE_PREFIX = 'SUBCHAMADO - ';

    /** Zera SLA/OLA e estatísticas; o GLPI recalcula a partir da nova data. */
    private const RESET_FIELDS = [
        'time_to_resolve'            => null,
        'time_to_own'                => null,
        'internal_time_to_resolve'   => null,
        'internal_time_to_own'       => null,
        'begin_waiting_date'         => null,
        'sla_waiting_duration'       => 0,
        'ola_waiting_duration'       => 0,
        'waiting_duration'           => 0,
        'ola_ttr_begin_date'         => null,
        'ola_tto_begin_date'         => null,
        'slalevels_id_ttr'           => 0,
        'olalevels_id_ttr'           => 0,
        'solvedate'                  => null,
        'closedate'                  => null,
        'takeintoaccountdate'        => null,
        'takeintoaccount_delay_stat' => 0,
        'solve_delay_stat'           => 0,
        'close_delay_stat'           => 0,
        'is_deleted'                 => 0,
    ];

    /** Campos restaurados a partir do original após o add() (regras podem alterar). */
    private const RESTORE_FIELDS = [
        'name', 'content', 'type', 'itilcategories_id', 'urgency', 'impact',
        'priority', 'locations_id', 'requesttypes_id', 'users_id_recipient',
        'actiontime', 'global_validation', 'validation_percent',
    ];

    /** Tabelas ligadas por tickets_id, copiadas direto. */
    private const TICKET_TABLES = [
        'glpi_tickets_users',
        'glpi_groups_tickets',
        'glpi_suppliers_tickets',
        'glpi_items_tickets',
        'glpi_ticketcosts',
    ];

    // ========================================================================
    // Aba lateral
    // ========================================================================

    public static function getTypeName($nb = 0)
    {
        return 'SubChamado';
    }

    /**
     * Só perfis que podem SER TÉCNICO de chamado (analistas, suporte, admin)
     * enxergam o botão. Isso exclui Self-Service e Observador puro.
     */
    public static function canUse(): bool
    {
        return Session::getCurrentInterface() === 'central'
            && Session::haveRight('ticket', Ticket::OWN)
            && Ticket::canCreate();
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            !($item instanceof Ticket)
            || $item->isNewItem()
            || $withtemplate
            || !self::canUse()
        ) {
            return '';
        }
        return self::createTabEntry(self::getTypeName());
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!($item instanceof Ticket) || $item->isNewItem()) {
            return false;
        }
        self::showForTicket($item);
        return true;
    }

    public static function getIcon()
    {
        return 'ti ti-copy';
    }

    private static function showForTicket(Ticket $ticket): void
    {
        $id  = (int) $ticket->getID();
        $url = Plugin::getWebDir('subticket') . '/front/clone.php';

        echo '<div class="card p-4 m-3">';
        echo '<h3 class="mb-3"><i class="ti ti-copy"></i> Criar SubChamado</h3>';
        echo '<p>Cria um novo chamado com título <strong>' . htmlspecialchars(self::TITLE_PREFIX, ENT_QUOTES)
            . '&lt;título do chamado atual&gt;</strong> e <strong>status Em atendimento</strong>, copiando todo o histórico '
            . '(acompanhamentos, tarefas, solução, validações, custos, ativos, documentos e atores) '
            . 'do chamado atual, preservando autores e datas originais.</p>';
        echo '<ul>';
        echo '<li>SLA/OLA reiniciados a partir da abertura do clone</li>';
        echo '<li>Nenhuma notificação é enviada ao clonar</li>';
        echo '<li>SubChamado vinculado automaticamente ao chamado original ("Relacionado a")</li>';
        echo '</ul>';
        $max      = self::MAX_COPIES;
        $base_url = htmlspecialchars(Ticket::getFormURL(), ENT_QUOTES);
        echo '<div class="mt-3 d-flex align-items-start gap-3 flex-wrap">';

        // Formulário (esquerda)
        echo '<div class="d-flex align-items-end gap-2 flex-wrap">';
        echo '<div>';
        echo '<label for="subticket-count" class="form-label mb-1">Quantos SubChamados você deseja criar?</label>';
        echo '<input type="number" id="subticket-count" class="form-control"'
            . ' value="1" min="1" max="' . $max . '" step="1"'
            . ' style="width:110px">';
        echo '</div>';
        echo '<button type="button" id="subticket-btn" class="btn btn-primary"'
            . ' data-ticket-id="' . $id . '"'
            . ' data-max="' . $max . '"'
            . ' data-ticket-base-url="' . $base_url . '"'
            . ' data-url="' . htmlspecialchars($url, ENT_QUOTES) . '">';
        echo '<i class="ti ti-copy"></i> Criar SubChamado';
        echo '</button>';
        echo '</div>';

        // Caixa de resultado (direita) — JS popula após cada clone bem-sucedido
        echo '<div id="subticket-result" class="flex-grow-1" style="min-width:280px;max-width:520px;display:none">';
        echo '<div class="alert alert-success mb-0">';
        echo '<div class="d-flex justify-content-between align-items-center mb-2">';
        echo '<strong><i class="ti ti-check"></i> SubChamados criados</strong>';
        echo '<button type="button" class="btn-close" id="subticket-result-close" aria-label="Fechar"></button>';
        echo '</div>';
        echo '<ul id="subticket-result-list" class="mb-0 ps-3"></ul>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        echo '<div class="form-text mt-1">Máximo de ' . $max . ' por operação. Você permanece no chamado original.</div>';
        echo '</div>';
    }

    // ========================================================================
    // Lógica de cópia
    // ========================================================================

    public static function cloneTicket(int $source_id): int
    {
        global $DB;

        $source = new Ticket();
        if (!$source->getFromDB($source_id)) {
            throw new \RuntimeException("Chamado $source_id não encontrado");
        }
        $f     = $source->fields;
        $title = self::subTicketTitle((string) $f['name']);

        $DB->beginTransaction();
        try {
            // 1) Clone nativo com SLA zerado e sem notificação
            $initial_status = self::CLONE_STATUS ?? (int) $f['status'];
            $override = self::RESET_FIELDS + [
                '_disablenotif' => true,
                'name'          => $title,
                'date'          => $_SESSION['glpi_currenttime'],
                'status'        => $initial_status,
            ];
            $new_id = $source->clone($override, false);
            if (!$new_id) {
                throw new \RuntimeException('O clone nativo falhou');
            }

            // 2) Limpa atores/ativos/custos criados automaticamente
            foreach (self::TICKET_TABLES as $table) {
                $DB->delete($table, ['tickets_id' => $new_id]);
            }

            // 3) Copia atores, ativos e custos do original
            foreach (self::TICKET_TABLES as $table) {
                self::copyRows($table, ['tickets_id' => $source_id], ['tickets_id' => $new_id]);
            }

            // 4) Histórico completo (autores e datas preservados)
            $followups = self::copyRows(
                'glpi_itilfollowups',
                ['itemtype' => 'Ticket', 'items_id' => $source_id],
                ['items_id' => $new_id],
                ['sourceitems_id' => 0, 'sourceof_items_id' => 0]
            );
            $tasks = self::copyRows(
                'glpi_tickettasks',
                ['tickets_id' => $source_id],
                ['tickets_id' => $new_id],
                ['sourceitems_id' => 0, 'sourceof_items_id' => 0],
                static fn(array $row): array => ['uuid' => self::uuid()] + $row
            );
            $solutions = self::copyRows(
                'glpi_itilsolutions',
                ['itemtype' => 'Ticket', 'items_id' => $source_id],
                ['items_id' => $new_id],
                [],
                static fn(array $row): array => [
                    'itilfollowups_id' => $followups[$row['itilfollowups_id'] ?? 0] ?? 0,
                ] + $row
            );
            $validations = self::copyRows(
                'glpi_ticketvalidations',
                ['tickets_id' => $source_id],
                ['tickets_id' => $new_id]
            );

            // 5) Documentos: re-vincula sem duplicar arquivo físico
            self::copyDocuments('Ticket', [$source_id => $new_id]);
            self::copyDocuments('ITILFollowup', $followups);
            self::copyDocuments('TicketTask', $tasks);
            self::copyDocuments('ITILSolution', $solutions);
            self::copyDocuments('TicketValidation', $validations);

            // 6) Restaura campos sensíveis que regras possam ter alterado (sem status ainda)
            $restore = [];
            foreach (self::RESTORE_FIELDS as $field) {
                $restore[$field] = $f[$field] ?? null;
            }
            $restore['name']     = $title; // restaurado do original + prefixo
            $restore['date_mod'] = $_SESSION['glpi_currenttime'];
            $DB->update('glpi_tickets', self::escape($restore), ['id' => $new_id]);

            // 7) Vínculo clone <-> original ("Vinculado a")
            $link = new Ticket_Ticket();
            if (!$link->add([
                'tickets_id_1'  => $new_id,
                'tickets_id_2'  => $source_id,
                'link'          => Ticket_Ticket::LINK_TO,
                '_disablenotif' => true,
            ])) {
                throw new \RuntimeException('Não foi possível vincular o clone ao original');
            }

            // 8) Status final — por último, para não ser sobrescrito pela automação
            //    "atribuição → Em atendimento" disparada por Ticket::prepareInputForUpdate
            $now    = $_SESSION['glpi_currenttime'];
            $status = self::CLONE_STATUS ?? (int) $f['status'];
            $final  = ['status' => $status, 'date_mod' => $now];
            if ($status >= Ticket::SOLVED) {
                $final['solvedate'] = $now;
            } else {
                $final['solvedate'] = null;
            }
            if ($status === Ticket::CLOSED) {
                $final['closedate'] = $now;
            } else {
                $final['closedate'] = null;
            }
            $DB->update('glpi_tickets', $final, ['id' => $new_id]);

            $DB->commit();
            return (int) $new_id;
        } catch (\Throwable $e) {
            $DB->rollBack();
            throw $e instanceof \RuntimeException ? $e : new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Título do SubChamado: "SUBCHAMADO - <título original>".
     * Não repete o prefixo se o chamado de origem já for um SubChamado.
     * Respeita o limite de 255 caracteres da coluna (sem cortar entidade HTML ao meio).
     */
    public static function subTicketTitle(string $name): string
    {
        $title = str_starts_with($name, self::TITLE_PREFIX) ? $name : self::TITLE_PREFIX . $name;

        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 255);
            $title = preg_replace('/&[#a-zA-Z0-9]*$/', '', $title);
        }
        return $title;
    }

    /**
     * Escapa valores lidos do banco antes de regravá-los.
     *
     * No GLPI 10, DBmysql::insert()/update() NÃO escapam strings: esperam
     * dados já tratados, como os que chegam do $_POST. O clone nativo
     * (Clonable::clone) faz o mesmo com Toolbox::addslashes_deep().
     */
    private static function escape(array $row): array
    {
        return Toolbox::addslashes_deep($row);
    }

    private static function copyRows(
        string $table,
        array $where,
        array $override = [],
        array $extra = [],
        ?callable $transform = null
    ): array {
        global $DB;

        $map = [];
        foreach ($DB->request(['FROM' => $table, 'WHERE' => $where, 'ORDER' => 'id ASC']) as $row) {
            $old_id = $row['id'];
            unset($row['id']);

            $new_row = $override + $row;
            if ($transform !== null) {
                $new_row = $transform($new_row);
            }
            foreach ($extra as $col => $val) {
                if (array_key_exists($col, $new_row)) {
                    $new_row[$col] = $val;
                }
            }

            if (!$DB->insert($table, self::escape($new_row))) {
                throw new \RuntimeException("Falha ao copiar linha de $table");
            }
            $map[$old_id] = $DB->insertId();
        }
        return $map;
    }

    private static function copyDocuments(string $itemtype, array $id_map): void
    {
        foreach ($id_map as $old_id => $new_id) {
            self::copyRows(
                'glpi_documents_items',
                ['itemtype' => $itemtype, 'items_id' => $old_id],
                ['items_id' => $new_id]
            );
        }
    }

    private static function uuid(): string
    {
        $b    = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
