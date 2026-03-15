# Backlog Técnico de Conformidade Fiscal (Moçambique)

Data: 15/03/2026
Escopo: módulo de faturação do plugin Toque Pro SiG Front.

## P1 (crítico)

### US-01 — Numeração fiscal inviolável na emissão
**Objetivo**: garantir que o número final seja definido apenas na emissão, sem colisões por tipo.

**Implementado nesta entrega**
- Emissão passou a usar tentativa de reserva com retries e validação de colisão.
- Número final só é atribuído para documento em `draft`.

**Critérios de aceite**
- Documento em `issued` nunca altera número.
- Não há dois documentos do mesmo tipo com mesmo número final.

### US-02 — Cancelamento com motivo obrigatório
**Objetivo**: tornar cancelamento auditável com justificativa.

**Implementado nesta entrega**
- Campo obrigatório `cancel_reason` no fluxo de cancelamento.
- Persistência de `cancel_reason`, `cancelled_at`, `cancelled_by`.

**Critérios de aceite**
- Não é possível cancelar sem motivo.
- Motivo aparece no documento cancelado.

### US-03 — Retenção na fonte no documento
**Objetivo**: suportar retenção percentual e total líquido a pagar.

**Implementado nesta entrega**
- Campos `withholding_rate` e `withholding_amount` no documento.
- Cálculo fiscal consolidado com `payable_total`.
- Atualização de retenção permitida apenas em `draft`.
- Exportação CSV e impressão/PDF passam a considerar retenção.

**Critérios de aceite**
- Taxa válida entre 0 e 100.
- Saldo pendente usa total líquido (bruto - retenção).

### US-04 — Trilho de auditoria fiscal
**Objetivo**: registrar eventos fiscais relevantes.

**Implementado nesta entrega**
- Nova tabela `tps_fiscal_events`.
- Eventos registrados para emissão, cancelamento e alteração de retenção.

**Critérios de aceite**
- Cada evento guarda `document_id`, `event_type`, `payload`, `user_id`, `created_at`.

---

## P2 (próxima iteração)

### US-05 — IVA por linha com isenção formal
- Regra por linha: tributado/isento/não sujeito.
- `motivo_isencao` obrigatório quando não tributado.

**Status atual**
- Entregue em 15/03/2026: modo fiscal por linha + motivo obrigatório para linhas não tributadas + cálculo de IVA por linha.
- Entregue em 15/03/2026 (complemento): catálogo estruturado de códigos legais de isenção + validação obrigatória de código + bloqueios por perfil/permissão fiscal no backend/UI.

### US-06 — Nota de crédito / débito
- Novos tipos fiscais com vínculo ao documento original.
- Reversão/ajuste fiscal sem cancelamento destrutivo.

**Status atual**
- Entregue em 15/03/2026: tipos `credit_note` e `debit_note` com vínculo obrigatório a documento original emitido.
- Entregue em 15/03/2026: validações no backend/UI, exibição do vínculo no detalhe/impressão e atalhos para criar notas a partir de documentos emitidos.

### US-07 — Exportação fiscal oficial para AT
- Gerador de layout fiscal oficial (versão parametrizável).
- Validador de estrutura antes do download.

**Status atual**
- Entregue em 15/03/2026 (MVP): endpoint `tps_export_fiscal_at` com layout estruturado AT e versão configurável via `fiscal_layout_version`.
- Entregue em 15/03/2026 (MVP): validação estrutural obrigatória antes do download + bloqueio com feedback quando houver configuração/estrutura inválida.

---

## P3 (maturidade)

### US-08 — Integridade criptográfica
- Hash encadeado por documento/evento fiscal.

**Status atual**
- Entregue em 15/03/2026 (MVP): cadeia de hash por documento fiscal (`fiscal_prev_hash` -> `fiscal_hash`) gerada em emissão e assegurada em cancelamento.
- Entregue em 15/03/2026 (MVP): cadeia de hash de eventos fiscais (`prev_event_hash` -> `event_hash`) gerada automaticamente em cada evento auditável.

### US-09 — Fecho fiscal mensal
- Consolidação mensal de documentos, recebimentos, IVA e retenções.

**Status atual**
- Entregue em 15/03/2026 (MVP): fecho fiscal mensal persistido por período (`AAAA-MM`) com consolidação de documentos emitidos/cancelados, IVA, retenções, total líquido, recebimentos e saldo em aberto.
- Entregue em 15/03/2026 (MVP): exportação CSV do fecho mensal e cadeia de integridade do fecho (`closure_prev_hash` -> `closure_hash`).
- Entregue em 15/03/2026 (complemento): histórico dos últimos fechos mensais na tela de documentos, com exportação por período diretamente na listagem.
