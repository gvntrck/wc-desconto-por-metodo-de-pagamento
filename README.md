# WC Ajuste Pagamento

Plugin para WordPress + WooCommerce que permite configurar um percentual de acrescimo ou desconto por metodo de pagamento em cada produto.

## Como funciona

1. Instale e ative o plugin no WordPress.
2. Edite um produto no WooCommerce.
3. Abra a aba `Ajuste por pagamento`.
4. Preencha o percentual desejado para cada gateway.
5. Use valor positivo para acrescimo e negativo para desconto.
6. No checkout, quando o cliente escolher o metodo de pagamento, o WooCommerce adiciona o ajuste automaticamente ao total e identifica se e desconto ou acrescimo.

## Observacoes

- O ajuste e somado no checkout com o rótulo `Acrescimo` ou `Desconto`, conforme o caso.
- Se o percentual ficar em branco ou `0`, nenhum valor extra e aplicado.
- Em variacoes, o plugin usa a configuracao salva no produto pai quando a variacao nao tiver configuracao propria.