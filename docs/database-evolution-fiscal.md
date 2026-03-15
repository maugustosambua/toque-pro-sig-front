# Evolução do Banco de Dados Fiscal (Moçambique)

Data: 15/03/2026  
Escopo: numeração inviolável, snapshot fiscal, auditoria e integridade referencial.

## 1) Objetivos de desenho

- Garantir numeração fiscal **imutável** após emissão.
- Garantir prova de conteúdo fiscal por documento (snapshot canônico + hash).
- Garantir trilha de auditoria **append-only** para operações críticas.
- Evoluir para integridade referencial no banco (FKs e regras de `ON UPDATE/DELETE`).
- Permitir migração incremental com baixo risco em ambiente WordPress.

---

## 2) Modelo alvo (v2)

## 2.1 Sequências fiscais (numeração inviolável)

### Nova tabela: `tps_fiscal_sequences`

```sql
CREATE TABLE tps_fiscal_sequences (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  doc_type VARCHAR(20) NOT NULL,
  series_code VARCHAR(30) NOT NULL DEFAULT 'A',
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  next_number BIGINT UNSIGNED NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_seq_scope (doc_type, series_code, fiscal_year)
) ENGINE=InnoDB;
```

### Alterações em `tps_documents`

- Manter `number` como legado/provisório.
- Adicionar colunas fiscais finais:

```sql
ALTER TABLE tps_documents
  ADD COLUMN fiscal_series VARCHAR(30) DEFAULT NULL,
  ADD COLUMN fiscal_year SMALLINT UNSIGNED DEFAULT NULL,
  ADD COLUMN fiscal_number BIGINT UNSIGNED DEFAULT NULL,
  ADD COLUMN issued_at DATETIME DEFAULT NULL,
  ADD COLUMN issued_by BIGINT UNSIGNED DEFAULT NULL,
  ADD UNIQUE KEY uq_fiscal_number (type, fiscal_series, fiscal_year, fiscal_number);
```

### Regra funcional

- Documento em `draft`: pode ter número de pré-visualização.
- Na emissão: reservar `fiscal_number` via sequência (`SELECT ... FOR UPDATE` na linha da sequência).
- Após emissão: bloquear update de `fiscal_series/fiscal_year/fiscal_number` na aplicação.

---

## 2.2 Snapshot fiscal por documento

### Nova tabela: `tps_document_snapshots`

```sql
CREATE TABLE tps_document_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  document_id BIGINT UNSIGNED NOT NULL,
  snapshot_type VARCHAR(30) NOT NULL, -- issued|cancelled|reissued
  canonical_payload LONGTEXT NOT NULL,
  payload_hash CHAR(64) NOT NULL,
  prev_snapshot_hash CHAR(64) DEFAULT NULL,
  created_by BIGINT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_snapshot_doc (document_id),
  KEY idx_snapshot_hash (payload_hash),
  UNIQUE KEY uq_doc_snapshot_hash (document_id, payload_hash),
  CONSTRAINT fk_snap_doc FOREIGN KEY (document_id)
    REFERENCES tps_documents(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
```

### Conteúdo canônico recomendado

- Cabeçalho fiscal do documento (`type`, `fiscal_series`, `fiscal_year`, `fiscal_number`, datas, cliente fiscal).
- Totais fiscais (`subtotal`, `iva`, `withholding_amount`, `payable_total`).
- Linhas fiscais ordenadas por `id` com `tax_mode`, `tax_rate`, `exemption_code`, valores.
- Referência de ajuste (`original_document_id`) quando aplicável.
- Metadados de emissão/cancelamento.

---

## 2.3 Auditoria fiscal append-only

### Evoluir `tps_fiscal_events` (já existente)

Adicionar (se ainda não existir no ambiente):

```sql
ALTER TABLE tps_fiscal_events
  ADD COLUMN actor_ip VARCHAR(45) DEFAULT NULL,
  ADD COLUMN user_agent VARCHAR(255) DEFAULT NULL,
  ADD COLUMN request_id CHAR(36) DEFAULT NULL,
  ADD COLUMN source VARCHAR(30) DEFAULT 'app',
  ADD KEY idx_request_id (request_id);
```

### Regra funcional

- Nunca atualizar/deletar eventos fiscais.
- Cada evento contém `prev_event_hash` e `event_hash` (cadeia já iniciada no projeto).
- Eventos mínimos: `document_issued`, `document_cancelled`, `withholding_updated`, `adjustment_linked`, `month_closed`.

---

## 2.4 Integridade referencial (FKs)

## Recomendação de engine

- Padronizar para `InnoDB` antes de aplicar FKs.
- Verificar collation/charset consistentes (`utf8mb4`).

### FKs prioritárias

```sql
ALTER TABLE tps_documents
  ADD CONSTRAINT fk_doc_customer FOREIGN KEY (customer_id)
    REFERENCES tps_customers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_doc_original FOREIGN KEY (original_document_id)
    REFERENCES tps_documents(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE tps_document_lines
  ADD CONSTRAINT fk_line_document FOREIGN KEY (document_id)
    REFERENCES tps_documents(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_line_item FOREIGN KEY (product_service_id)
    REFERENCES tps_products_services(id)
    ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE tps_payments
  ADD CONSTRAINT fk_pay_document FOREIGN KEY (document_id)
    REFERENCES tps_documents(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_pay_customer FOREIGN KEY (customer_id)
    REFERENCES tps_customers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE tps_payment_allocations
  ADD CONSTRAINT fk_alloc_payment FOREIGN KEY (payment_id)
    REFERENCES tps_payments(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_alloc_document FOREIGN KEY (document_id)
    REFERENCES tps_documents(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;
```

---

## 3) Regras de consistência (nível aplicação + banco)

- `status='issued'` => `issued_at`, `fiscal_series`, `fiscal_year`, `fiscal_number` obrigatórios.
- `status='draft'` => campos fiscais finais nulos.
- `type IN ('credit_note','debit_note')` => `original_document_id` obrigatório e original deve ser emitido.
- `tax_mode!='taxable'` => `exemption_code` obrigatório/validado por catálogo.
- `withholding_rate` entre 0 e 100.

## Recomendação adicional (opcional): constraints SQL

```sql
ALTER TABLE tps_documents
  ADD CONSTRAINT chk_withholding_rate CHECK (withholding_rate >= 0 AND withholding_rate <= 100);
```

> Nota: validar compatibilidade de `CHECK` com versão MySQL/MariaDB do ambiente.

---

## 4) Plano de migração incremental (sem downtime)

## Fase A — Expand

1. Criar novas tabelas (`tps_fiscal_sequences`, `tps_document_snapshots`).
2. Adicionar novas colunas em `tps_documents` e índices novos.
3. Adicionar colunas de auditoria em `tps_fiscal_events`.
4. Sem alterar comportamento de emissão ainda.

## Fase B — Backfill

1. Popular `fiscal_series='A'`, `fiscal_year=YEAR(issue_date)`, `fiscal_number=number` para documentos já emitidos/cancelados.
2. Gerar snapshots iniciais para emitidos/cancelados sem snapshot.
3. Criar linhas de `tps_fiscal_sequences` por `doc_type + series + year` com `next_number = MAX(fiscal_number)+1`.

## Fase C — Dual-write

1. Emissão passa a escrever número final em colunas fiscais novas.
2. Criar snapshot no evento de emissão/cancelamento.
3. Manter `number` sincronizado por compatibilidade de telas legadas.

## Fase D — Enforce

1. Ativar validações rígidas no app (imutabilidade de número fiscal final).
2. Aplicar FKs gradualmente (por domínio: documentos -> linhas -> pagamentos).
3. Habilitar exportações usando somente campos fiscais finais.

## Fase E — Clean-up (opcional)

- Descontinuar uso de `number` legado em relatórios/exportações.

---

## 5) Índices recomendados

- `tps_documents(type, fiscal_series, fiscal_year, fiscal_number)` único.
- `tps_documents(status, issue_date)` para fecho mensal.
- `tps_fiscal_events(document_id, created_at)` para auditoria por documento.
- `tps_document_snapshots(document_id, created_at)` para histórico fiscal.
- `tps_payments(payment_date)` para consolidação mensal.

---

## 6) Riscos e mitigação

- **dbDelta e FKs**: dbDelta nem sempre gerencia FKs de forma robusta; para FKs usar migrações SQL explícitas e idempotentes.
- **Ambiente legado com órfãos**: rodar relatório de órfãos antes de ativar FK (`LEFT JOIN ... WHERE child.fk IS NOT NULL AND parent.id IS NULL`).
- **Concorrência na emissão**: usar transação + lock da linha de sequência.
- **Diferença com regra contábil local**: confirmar série/ano/regras de arredondamento com contabilista antes de bloquear constraints.

---

## 7) Checklist de homologação técnica

- [ ] Emissão concorrente não gera duplicidade de número fiscal.
- [ ] Documento emitido não permite alteração de número fiscal.
- [ ] Snapshot fiscal é gerado em emissão e cancelamento.
- [ ] Hash de snapshot/evento encadeia corretamente.
- [ ] Fecho mensal usa apenas dados fiscalmente válidos.
- [ ] Todas as FKs críticas ativas sem órfãos.
- [ ] Exportação fiscal usa campos finais (`fiscal_*`) e não pré-visualização.
