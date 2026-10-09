# SubChamado (subticket) — Plugin para GLPI 10

Cria **SubChamados** a partir de um chamado: cópias completas, com todo o histórico do original, preservando autores e datas, e vinculadas ao chamado de origem. Serve para dividir uma demanda entre equipes ou etapas sem perder o contexto.

| | |
|---|---|
| **Versão** | 0.5.0 |
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
| Acompanhamentos, tarefas, solução e validações | Copiados com **autores e datas originais** |
| Documentos | **Vinculados** aos mesmos arquivos (sem duplicar no disco) |
| Ativos e custos | Copiados |
| Vínculo | "Relacionado a" o chamado original |
| Status | **Em atendimento** (2) |
| SLA / OLA | Zerados e recalculados a partir da abertura do SubChamado |
| Notificações | **Nenhuma** é enviada |

Cada SubChamado é criado numa **transação**: se algo falhar, nada daquele SubChamado fica gravado.

## Instalação

```bash
cd /var/www/html/glpi/plugins
git clone https://github.com/andersonthales/subticket.git
chown -R www-data:www-data subticket
```

Em **Configurar → Plugins**, clique em **Instalar** e depois em **Ativar**. O plugin não cria tabelas.

## Ajustes

As opções são constantes em `inc/cloner.class.php`:

| Constante | Padrão | Efeito |
|---|---|---|
| `CLONE_STATUS` | `2` | Status inicial do SubChamado. `null` mantém o status do original |
| `MAX_COPIES` | `20` | Máximo de SubChamados por operação |
| `TITLE_PREFIX` | `SUBCHAMADO - ` | Prefixo do título |

## Limitações conhecidas

- **Apóstrofos quebram a cópia.** Se o título, a descrição ou algum acompanhamento tiver `'`, a criação do SubChamado falha. Atualize para a versão mais recente.
- Dados de **outros plugins** (campos adicionais, pesquisas de satisfação etc.) não são copiados.
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
