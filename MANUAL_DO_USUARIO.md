# Manual do Usuário — Levy

O Levy é um controle financeiro pessoal. Ele registra receitas e despesas, permite dividir compras com amigos, acompanha valores a receber e organiza contas recorrentes.

## Primeiro acesso

1. Abra o endereço do sistema no navegador.
2. Se esta for uma instalação nova, você será levado para a tela de configuração inicial. Informe nome, e-mail e uma senha com pelo menos 12 caracteres.
3. A primeira conta criada é administradora. Depois, entre com e-mail e senha na tela de login.

Cada usuário tem dados próprios: lançamentos, categorias, cartões, amigos, contas fixas e recebimentos não são compartilhados entre contas.

## Ordem recomendada para começar

Antes de registrar despesas, cadastre as informações que aparecem nos seletores dos lançamentos:

1. **Categorias**: crie as categorias de receita e de despesa (por exemplo, Salário, Alimentação e Transporte).
2. **Cartões**: cadastre seus cartões ou formas de pagamento que queira identificar.
3. **Amigos**: cadastre pessoas com quem costuma dividir gastos.
4. **Meu Perfil**: informe salário base e saldo inicial, se quiser que o Dashboard calcule o saldo disponível.
5. Comece a lançar suas transações.

## Dashboard

O Dashboard mostra o mês selecionado e reúne:

- **Saldo disponível**: saldo inicial + suas receitas − suas despesas − contas fixas automáticas ainda não baixadas.
- **A receber**: parcelas pendentes que amigos devem pagar.
- **Minhas despesas**: somente a sua parcela dos gastos, sem incluir a parte dos amigos.
- Gráfico de despesas por categoria e os lançamentos recentes.

Para consultar outro período, escolha o mês no topo. Na lista de lançamentos, pesquise por descrição, valor, amigo ou categoria. O botão **Filtro Avançado** permite restringir por categoria, por uma pessoa específica ou apenas pelos seus gastos.

## Cadastrar uma transação

Abra **Nova Conta** (ou o botão **Nova Transação** no Dashboard) e preencha:

- **Descrição** e **valor total**;
- **Tipo**: *Saída (Despesa)* ou *Entrada (Receita)*;
- Categoria e cartão/forma de pagamento, se aplicável;
- **Data da compra** e **mês de referência**. O mês de referência define em qual mês o lançamento aparecerá nos painéis e relatórios.

### Compra só sua

Na seção **Divisão da Conta**, mantenha apenas a linha **Minha Parte** e informe o valor total nela. A soma das divisões precisa ser exatamente igual ao valor total da transação.

### Compra dividida com amigos

1. Informe o valor total.
2. Em **Divisão da Conta**, mantenha ou ajuste o valor da sua parte.
3. Clique em **Adicionar Amigo**, selecione a pessoa e informe quanto ela deve.
4. Confira se a soma da sua parte e das partes dos amigos é igual ao valor total.
5. Salve.

A parte atribuída a um amigo fica pendente e aparece em **Recebimentos**. A sua parte compõe suas despesas e os gráficos.

### Compra parcelada

1. Marque **Compra parcelada**.
2. Informe a quantidade de parcelas e o mês da primeira parcela.
3. Clique em **Gerar parcelas**.
4. Revise o valor de cada parcela e a divisão entre participantes. Se necessário, use **Dividir igualmente** em cada parcela.
5. Salve após conferir que, em cada parcela, a soma das partes é igual ao valor daquela parcela.

O Levy cria um lançamento para cada parcela, em meses de referência consecutivos, com a descrição identificando a parcela (por exemplo, `1/3`).

## Editar ou excluir um lançamento

No Dashboard ou em **Relatórios / Gráficos**, use a ação de editar ao lado da transação. É possível alterar dados e os valores do rateio; novamente, a soma das divisões deve bater com o total. A exclusão pede confirmação e também remove as divisões de amigos associadas.

## Contas fixas e assinaturas

Em **Contas Fixas**, cadastre um molde recorrente com descrição, valor estimado, dia de vencimento, tipo de pagamento e, opcionalmente, cartão vinculado.

O cadastro não cria uma despesa automaticamente. Quando a conta for paga no mês atual, clique em **Baixar Pago**. O sistema cria então uma despesa real para esse mês e marca o molde como pago. Para deixar de acompanhar uma conta, use o ícone de remover; os lançamentos já realizados são preservados.

Contas marcadas como **Débito Automático / Cartão** entram como compromisso no cálculo do saldo disponível enquanto ainda não tiverem sido baixadas no mês.

## Recebimentos de amigos

Abra **Recebimentos** para ver, por mês, as parcelas pendentes de cada amigo e suas próprias despesas para conferência.

- Use **Confirmar Pagamento** para dar baixa em um item específico.
- Use **Quitar Tudo** para confirmar todos os itens pendentes daquele amigo no mês selecionado.
- Clique na área do amigo para expandir os detalhes. O relatório individual pode ser usado para compartilhar a cobrança; quando o recurso de PDF estiver disponível na instalação, ele é baixado como PDF.

Dar baixa apenas muda o status da dívida; não altera o lançamento original nem a sua parcela da compra.

## Relatórios e gráficos

Em **Relatórios / Gráficos**, selecione o mês para ver o detalhamento de transações e os gastos por categoria. O gráfico considera exclusivamente as suas parcelas de transações classificadas como despesa.

## Meu Perfil

Em **Meu Perfil**, você pode atualizar:

- Nome exibido no sistema;
- Salário base mensal, usado na análise de comprometimento por categoria;
- Saldo inicial, usado no Dashboard;
- Senha de acesso.

Para trocar a senha, preencha senha atual, nova senha e confirmação. A nova senha deve ter pelo menos 12 caracteres. O e-mail de acesso não é alterado nesta tela.

## Administração de usuários

Disponível apenas para administradores, o menu **Usuários** permite criar novas contas e ativá-las ou desativá-las. Ao criar uma conta, informe nome, e-mail e uma senha temporária com pelo menos 12 caracteres. Usuários novos começam com categorias iniciais e não têm acesso aos dados de outros usuários.

## Dicas importantes

- Use sempre o **mês de referência** para representar quando o valor deve afetar o seu planejamento, especialmente em compras parceladas.
- Em rateios, os valores das partes devem fechar exatamente com o total da compra.
- Categorias de receita e despesa ajudam a manter os relatórios corretos.
- Saia pelo botão **Sair** ao terminar, principalmente em dispositivos compartilhados.
