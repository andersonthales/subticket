# Changelog

## [0.5.1] - 2026-10-08
### Corrigido
- Valores copiados do chamado original passam a ser escapados antes de gravar, como faz o clone nativo do GLPI. Antes, um apóstrofo no título, na descrição ou num acompanhamento fazia a criação do SubChamado falhar

## [0.5.0] - 2026-10-08
Versão em produção (central-agetic: atendimento e homologação).
- Aba lateral "SubChamado" no chamado
- Cópia completa: atores, ativos, custos, acompanhamentos, tarefas, solução, validações e documentos, com autores e datas originais
- Título com prefixo `SUBCHAMADO - `, sem repetir em cópias de cópias
- Status inicial configurável (`CLONE_STATUS`), SLA/OLA reiniciados, sem notificações
- Vínculo "Relacionado a" com o original
- Criação de vários SubChamados por vez (até `MAX_COPIES`)
