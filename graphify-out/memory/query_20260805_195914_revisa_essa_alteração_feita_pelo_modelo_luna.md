---
type: "query"
date: "2026-08-05T19:59:14.601362+00:00"
question: "revisa essa alteração feita pelo modelo luna"
contributor: "graphify"
outcome: "useful"
source_nodes: [".render_product_data_panel()", "WC_Metodo_Pagamento_Acrescimo", ".apply_payment_method_surcharge()"]
---

# Q: revisa essa alteração feita pelo modelo luna

## Answer

Expanded from original query via vocab: render, product, data, panel, save, format, surcharge, payment, text. Revisão encontrou um bloqueador financeiro: o texto promete aceitar vírgula e ponto, mas wc_format_decimal só converte a vírgula quando ela faz parte dos separadores configurados ou do locale. Com separador decimal ponto, 3,97 pode virar 397. O placeholder também não está internacionalizado. Sintaxe PHP e diff check passaram.

## Outcome

- Signal: useful

## Source Nodes

- .render_product_data_panel()
- WC_Metodo_Pagamento_Acrescimo
- .apply_payment_method_surcharge()