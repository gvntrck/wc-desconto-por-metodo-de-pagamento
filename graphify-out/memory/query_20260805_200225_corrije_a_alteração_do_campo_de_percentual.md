---
type: "query"
date: "2026-08-05T20:02:25.911419+00:00"
question: "corrije a alteração do campo de percentual"
contributor: "graphify"
outcome: "useful"
source_nodes: [".render_product_data_panel()", "WC_Metodo_Pagamento_Acrescimo", ".normalize_surcharges()", ".apply_payment_method_surcharge()"]
---

# Q: corrije a alteração do campo de percentual

## Answer

Expanded from original query via vocab: render, product, save, format, normalize, surcharge, payment, text. Corrigido o fluxo completo: o campo de texto agora tem pattern para sinal opcional e decimal com ponto ou vírgula; placeholder, título e rótulo acessível são traduzíveis; no servidor a entrada precisa ser escalar, vírgulas são normalizadas para ponto e o formato é validado antes de wc_format_decimal. Casos válidos e inválidos foram exercitados, além de php -l e git diff --check.

## Outcome

- Signal: useful

## Source Nodes

- .render_product_data_panel()
- WC_Metodo_Pagamento_Acrescimo
- .normalize_surcharges()
- .apply_payment_method_surcharge()