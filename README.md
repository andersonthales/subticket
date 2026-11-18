# SubChamado (subticket) — Plugin para GLPI 10

Cria **SubChamados** a partir de um chamado: cópias completas, com todo o histórico do original, preservando autores e datas, e vinculadas ao chamado de origem. Serve para dividir uma demanda entre equipes ou etapas sem perder o contexto.

| | |
|---|---|
| **Versão** | 1.1.0 |
| **GLPI** | 10.0.x |
| **PHP** | 8.1 ou superior |
| **Licença** | GPLv2+ |

---

## Como usar

1. Abra um chamado e clique na aba lateral **SubChamado**.
2. Informe **quantos SubChamados** criar (até 20 por vez) e clique em **Criar SubChamado**.
3. Os números criados aparecem numa caixa ao lado, com links. Você continua no chamado original.

A aba aparece apenas para quem usa a **interface padrão** (não o Self-Service), pode **ser técnico** de chamados (direito *Chamado → Ser atribuído*) e pode **criar** chamados.

## O que é copiado

| Item | Como fica no SubChamado |
|---|---|
| Título | `SUBCHAMADO - <título original>` (o prefixo não se repete ao clonar um SubChamado) |
| Descrição, tipo, categoria, urgência, impacto, prioridade, local, origem | Iguais ao original |
| Requerentes, observadores, técnicos, grupos e fornecedores | Iguais ao original |
| Quem cria o SubChamado | Incluído como **observador** |
| Campos adicionais (plugin fields) | Iguais ao original |
| Acompanhamentos, tarefas, solução e validações | Copiados com **autores e datas originais** |
| Documentos | **Vinculados** aos mesmos arquivos (sem duplicar no disco) |
| Ativos e custos | Copiados |
| Vínculo | "Relacionado a" o chamado original |
| Status | **Em atendimento** (2) |
| SLA / OLA | Zerados e recalculados a partir da abertura do SubChamado |
| Notificações | **Nenhuma** é enviada |

Cada SubChamado é criado numa **transação**: se algo falhar, nada daquele SubChamado fica gravado.

### Quem cria o SubChamado

O usuário que clica em **Criar SubChamado** é incluído como **observador** do novo chamado, com notificações ligadas. Se ele já era observador no chamado original, não é incluído de novo. Como nenhuma notificação é enviada no momento do clone, ele passa a receber só as atualizações seguintes.

### Campos adicionais

Com o plugin [Fields](https://github.com/pluginsGLPI/fields) instalado, os valores de **todos os blocos de chamado** (tabelas `glpi_plugin_fields_ticket*`) são copiados do original. Se o GLPI tiver criado uma linha vazia para o SubChamado, ela é substituída pela cópia. Um bloco novo passa a ser copiado sem mudar o plugin. Sem o Fields, esse passo é ignorado.

## Instalação

```bash
cd /var/www/html/glpi/plugins
git clone https://github.com/andersonthales/subticket.git
chown -R www-data:www-data subticket
```

Em **Configurar → Plugins**, clique em **Instalar** e depois em **Ativar**. O plugin não cria tabelas.

Pelo terminal:

```bash
cd /var/www/html/glpi
sudo -u www-data php bin/console plugin:install -n -u glpi subticket
sudo -u www-data php bin/console plugin:activate subticket
```

## Atualização

1. Substitua os arquivos da pasta `plugins/subticket` (ou `git pull`).
2. **Espere alguns segundos** se o PHP usar opcache com `validate_timestamps` (o padrão confere os arquivos a cada `revalidate_freq` segundos). Uma requisição que ainda leia o `setup.php` antigo marca o plugin como "a atualizar" de novo.
3. Reinstale e ative. Toda troca de versão desativa o plugin até esse passo:

```bash
sudo -u www-data php bin/console plugin:install --force -n -u glpi subticket
sudo -u www-data php bin/console plugin:activate subticket
```

Não há migração de banco entre versões.

## Ajustes

As opções são constantes em `inc/cloner.class.php`:

| Constante | Padrão | Efeito |
|---|---|---|
| `CLONE_STATUS` | `2` | Status inicial do SubChamado. `null` mantém o status do original |
| `MAX_COPIES` | `20` | Máximo de SubChamados por operação |
| `TITLE_PREFIX` | `SUBCHAMADO - ` | Prefixo do título |

## Limitações conhecidas

- Dados de **outros plugins** (pesquisas de satisfação etc.) não são copiados; os campos adicionais do plugin Fields são.
- Blocos do Fields ligados a **tarefas ou acompanhamentos** não são copiados, só os do próprio chamado.
- Validações pendentes são copiadas como pendentes, sem notificar o validador.
- O tempo gasto (`actiontime`) é copiado, então aparece somado em dobro nas estatísticas.

## Estrutura

```
subticket/
├── setup.php                # Registro do plugin, aba e assets
├── hook.php                 # Instalação / desinstalação (sem tabelas)
├── inc/cloner.class.php     # Aba lateral e lógica de cópia
├── front/clone.php          # Endpoint POST (valida permissão e CSRF)
├── js/subticket.js          # Botão, confirmação e caixa de resultado
└── css/subticket.css
```

## Changelog

Veja [CHANGELOG.md](CHANGELOG.md).

## Licença

[GPLv2 ou posterior](LICENSE).

## Autor

**Anderson Thales** — [@andersonthales](https://github.com/andersonthales)
