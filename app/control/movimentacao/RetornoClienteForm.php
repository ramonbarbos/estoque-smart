<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCheckList;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Form\TSpinner;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class RetornoClienteForm extends TPage
{
    private $form;
    private $isAtualizado = false;

    use Adianti\base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        parent::setTargetContainer('adianti_right_panel');
        $this->setAfterSaveAction(new TAction(['RetornoClienteList', 'onReload'], ['register_state' => 'true']));

        $this->setDatabase('sample');
        $this->setActiveRecord('Retorno_Cliente');

        // Cria um array com as opções de escolha


        // Criação do formulário
        $this->form = new BootstrapFormBuilder('form_retorno');
        $this->form->setFormTitle('Retorno de Saida');
        $this->form->setClientValidation(true);
        $this->form->setColumnClasses(2, ['col-sm-5 col-lg-4', 'col-sm-7 col-lg-8']);


        try {
            TTransaction::open('sample');

            $criteria = new TCriteria;
            $criteria->add(new TFilter('quant_retirada', '>', 0));
            $criteria->setProperty('order', 'id');
            // Use o critério para buscar os registros do banco de dados
            $repository = new TRepository('Saida');
            $items = $repository->load($criteria);

            // Crie um array de itens no formato chave-valor
            $options = [];
            foreach ($items as $item) {
                $options[$item->id] = $item->id;
            }

            TTransaction::close(); // Feche a transação quando terminar
        } catch (Exception $e) {
            // Lida com exceções aqui, como TTransaction não pode ser aberto
            echo 'Erro: ' . $e->getMessage();
            TTransaction::rollback(); // Se ocorrer um erro, faça rollback na transação
        }


        // Criação de fields
        $id = new TEntry('id');
        $entrada = new TDBCombo('saida_id', 'sample', 'Saida', 'id', 'id');
        $entrada->setChangeAction(new TAction([$this, 'onProdutoChange']));
        $entrada->setId('form_entrada');
        $produto = new TEntry('produto_id');
        $produto->setId('form_produto');
        $produto_nome = new TEntry('produto_nome');
        $produto_nome->setId('form_produto_nome');
        $datas    = new TDate('data_retorno');
        $datas->setId('form_data');
        $cliente = new TEntry('cliente_id');
        $cliente->setId('form_cliente');
        $cliente_nome    = new TEntry('cliente_nome');
        $cliente_nome->setId('form_cliente_nome');
        $nf = new TEntry('nota_fiscal');
        $nf->setId('form_nota_fiscal');
        $qtd_disponivel = new TEntry('quantidade_disponivel');
        $qtd_disponivel->setId('form_quantidade_disponivel');
        $valor_disponivel = new TEntry('valor_disponivel');
        $valor_disponivel->setId('form_valor_disponivel');

        $entrada_id = new TEntry('estoque_id');
        $entrada_id->setId('form_entrada_id');

        $motivo = new TText('motivo');

        $valor = new TEntry('preco_unit');
        $valor->setProperty('onkeyup', 'calcularValorTotal()'); // Adicione esta linha
        $valor->setId('form_preco_unit'); // Defina o ID como 'form_entrada_valor_total'

        $qtd = new TEntry('quantidade');
        $qtd->setId('form_quantidade'); // Defina o ID como 'form_entrada_valor_total'
        $qtd->setProperty('onkeyup', 'calcularValorTotal()'); // Adicione esta linha


        // Campo de total
        $total = new TEntry('valor_total');
        $total->setId('form_valor_total'); // Defina o ID como 'form_entrada_valor_total'
        $total->setProperty('onkeyup', 'calcularValorTotal()'); // Adicione esta linha

        $entrada->addItems($options);


        // Adicione fields ao formulário
        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Saida')], [$entrada, $entrada_id,$produto_nome,$produto],);
        $this->form->addFields([new TLabel('Nota Fiscal')], [$nf]);
        $this->form->addFields([new TLabel('Data de Retorno')], [$datas]);
        $this->form->addFields([new TLabel('Cliente')], [$cliente_nome, $cliente]);
        $this->form->addFields([new TLabel('Quantidade Disponível')], [$qtd_disponivel]);
        $this->form->addFields([new TLabel('Quantidade')], [$qtd]);
        $this->form->addFields([new TLabel('Valor Disponível')], [$valor_disponivel]);
        $this->form->addFields([new TLabel('Valor unidade')], [$valor]);
        $this->form->addFields([new TLabel('Total')], [$total]);
        $this->form->addFields([new TLabel('Motivo')], [$motivo]);

        // Validação do campo Nome
        $entrada->addValidation('Estoque', new TRequiredValidator);
        $cliente_nome->addValidation('Fornecedor', new TRequiredValidator);
        $datas->addValidation('Data de Retorno', new TRequiredValidator);
        $motivo->addValidation('Motivo', new TRequiredValidator);


        // Tornar o campo ID não editável
        $id->setEditable(false);
        $nf->setEditable(false);
        $qtd_disponivel->setEditable(false);
        $valor_disponivel->setEditable(false);
        $valor->setEditable(false);
        $cliente_nome->setEditable(false);
        $cliente->style = 'display: none;';
        $entrada_id->style = 'display: none;';
        $produto->style = 'display: none;';
        // Tamanho dos campos
        $id->setSize('100%');
        $entrada->setSize('50%');
        $entrada->enableSearch();
        $cliente->setSize('100%');
        //$datas->setDatabaseMask('yyyy-mm-dd');
        $datas->setMask('dd/mm/yyyy');
        $nf->setNumericMask(2, '', '', true);
        // $qtd_disponivel->setRange(0, 100, 1);
        $qtd_disponivel->setValue('0');
        $qtd->setValue('0');
        $valor_disponivel->setNumericMask(2, ',', '.', true);
        $valor_disponivel->setValue('0,00');

        //  $valor->setNumericMask(2, ',', '.', true);
        $valor->setSize('100%');
        $valor->setValue('0,00');
        $total->setValue('0,00');
        //$total->setNumericMask(2, ',', '.', true);

        $motivo->setSize('100%');



        TScript::create('function calcularValorTotal() {
            var quantidadeField = document.getElementById("form_quantidade");
            var valorUnitarioField = document.getElementById("form_preco_unit");
        
            if (quantidadeField && valorUnitarioField) {
                var quantidade = parseFloat(quantidadeField.value.replace(/\./g, ""));
                var valorUnitario = parseFloat(valorUnitarioField.value);
        
                console.log("Quantidade:", quantidade);
                console.log("Valor Unitário:", valorUnitario);
        
                if (!isNaN(quantidade) && !isNaN(valorUnitario)) {
                    var valorTotal = quantidade * valorUnitario;
                    var formattedTotal = formatarNumero(valorTotal);
                    document.getElementById("form_valor_total").value = formattedTotal;
        
                }
            }
        }
        
        function formatarNumero(numero) {
            var numeroFormatado = numero.toFixed(2);
            console.log("Valor Total:", numeroFormatado);
            return numeroFormatado;
        }');

        // Adicionar botão de salvar
        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:plus green');
        $btn->class = 'btn btn-sm btn-primary';

        // Adicionar link para criar um novo registro
        $this->form->addActionLink(_t('New'), new TAction([$this, 'onEdit']), 'fa:eraser red');

        // Adicionar link para fechar o formulário
        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        // Vertical container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }



    public static function onProdutoChange($param)
    {
        try {
            TTransaction::open('sample');

            if (isset($param['saida_id'])) {
                $saida_id = $param['saida_id'];

                // Faça uma consulta no banco de dados para obter a nota fiscal correspondente
                $saida = new Saida($saida_id);
                $nota_fiscal = $saida->nota_fiscal;
                $estoque_id = $saida->estoque_id;
                $produto = $saida->produto_id;
                $produto_nome = $saida->produto->nome;
                $cliente_id = $saida->cliente_id;
                $cliente_nome = $saida->cliente->nome;
                $qtd_disponivel = $saida->quantidade;
                $valor_disponivel = $saida->valor_total; // Suponhamos que o campo no banco de dados se chama "valor"
                $valor = $saida->preco_unit; // Suponhamos que o campo no banco de dados se chama "valor"


                TTransaction::close();

                // Preencha o campo de Nota Fiscal com o valor obtido
                TScript::create("document.getElementById('form_nota_fiscal').value = '{$nota_fiscal}';");
                TScript::create("document.getElementById('form_quantidade_disponivel').value = '{$qtd_disponivel}';");
                TScript::create("document.getElementById('form_valor_disponivel').value = '{$valor_disponivel}';");
                TScript::create("document.getElementById('form_preco_unit').value = '{$valor}';");
                TScript::create("document.getElementById('form_produto').value = '{$produto}';");
                TScript::create("document.getElementById('form_produto_nome').value = '{$produto_nome}';");
                TScript::create("document.getElementById('form_cliente').value = '{$cliente_id}';");
                TScript::create("document.getElementById('form_cliente_nome').value = '{$cliente_nome}';");
                TScript::create("document.getElementById('form_entrada_id').value = '{$estoque_id}';");
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }



    public function onSave($param)
    {
        try {
            TTransaction::open('sample');

            $retorno = new Retorno_Cliente();
            $retorno->fromArray($param);

            $data_retorno = DateTime::createFromFormat('d/m/Y', $retorno->data_retorno);
            if ($data_retorno) {
                $retorno->data_retorno = $data_retorno->format('Y-m-d');
            }

            // Verifique se já existe um registro de estoque correspondente a esta saída
            $mapaEstoque = Estoque::where('saida_id', '=', $retorno->saida_id)->load();

            if ($mapaEstoque) {
                $mapaEstoque = $mapaEstoque[0];

                $valorTotal = $mapaEstoque->valor_total;
                $QuantidadeTotal = $mapaEstoque->quantidade;


                $mapaEstoque->quantidade += $retorno->quantidade;
                $mapaEstoque->valor_total = $mapaEstoque->quantidade * $retorno->preco_unit;
                $mapaEstoque->quant_retirada += $retorno->quantidade;
                $mapaEstoque->updated_at = date('Y-m-d H:i:s');
                

                $mediaPonderadaEstoque = ($retorno->valor_total + $valorTotal) /( $retorno->quantidade+ $QuantidadeTotal) ;
                $mapaEstoque->valor_total = $mapaEstoque->quantidade * $mediaPonderadaEstoque;
                $mapaEstoque->preco_unit = $mediaPonderadaEstoque;

                $mapaEstoque->store();

                $saida = Saida::where('id', '=', $retorno->saida_id)
                    ->first();
                $saida->quant_retirada =  $saida->quant_retirada - $retorno->quantidade;
                $saida->store();

                // Salve o registro de retorno do cliente
                $retorno->store();

                $this->createMovement($retorno);
                TTransaction::close();

                new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $this->afterSaveAction);
            } else {
                TTransaction::close();
                throw new Exception('Não foi encontrada uma entrada correspondente no mapa de estoque para esta saída.');
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    private function createMovement($retorno)
    {
        try {
            //GRAVANDO MOVIMENTAÇÃO
            $mov = new Movimentacoes();
            $prod = new Produto($retorno->produto_id);
            $usuario_logado = TSession::getValue('userid');
            $descricao = 'Retorno de Saida ' . $prod->nome . ' - ' . $retorno->quantidade . ' unidades - NF:' . $retorno->nota_fiscal;
            $mov->data_hora = $retorno->data_retorno;
            $mov->descricao = $descricao;
            $mov->valor_total = $retorno->valor_total;
            $mov->produto_id = $retorno->produto_id;
            $mov->responsavel_id = $usuario_logado;
            $mov->quantidade = $retorno->quantidade;

            $estoque = Estoque::where('produto_id', '=', $retorno->produto_id)->first();
            if ($estoque->valor_total) {
                $mov->saldoEstoque = $estoque->valor_total;
            } else {
                $mov->saldoEstoque = 0;
            }
            $mov->store();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    private function atualizarEstoque($saida_id, $quantidadeReposta)
    {
        try {
            TTransaction::open('sample');

            error_log('Dentro da função atualizarEstoque');
            error_log('saida_id: ' . $saida_id);
            error_log('quantidadeReposta: ' . $quantidadeReposta);


            new TMessage('error', $saida_id);


            $entrada = Estoque::where('saida_id', '=', $saida_id)
                ->first();

            // Calcula a nova quantidade em estoque após a reposição
            $novaQuantidade = $entrada->quantidade + $quantidadeReposta;
            $valorReposto = $entrada->preco_unit * $quantidadeReposta;
            $novoValor = $entrada->valor_total + $valorReposto;


            // Atualiza a quantidade e o valor total em estoque no objeto
            $entrada->quantidade = $novaQuantidade;
            $entrada->valor_total = $novoValor;
            $entrada->quant_retirada =  $novaQuantidade;


            // Atualiza o registro no banco de dados
            $entrada->store();

            $saida = Saida::where('id', '=', $saida_id)
                ->first();
            $saida->quant_retirada =  $saida->quant_retirada - $quantidadeReposta;
            $saida->store();

            // Atualiza o campo de quantidade disponível no formulário usando JavaScript
            TScript::create("document.getElementById('form_quantidade_disponivel').value = '{$novaQuantidade}';");

            TTransaction::close();
            $this->isAtualizado = true;
        } catch (Exception $e) {
            // Trate a exceção de acordo com suas necessidades
            TTransaction::rollback();
            throw $e;
        }
    }

    public  function onEdit($param)
    {
        try {

            if (isset($param['key'])) {

                TTransaction::open('sample');
                $key = $param['key']; // A chave primária do registro que está sendo editado
                $retorno = new Retorno_Cliente($key);

                $saida_id = $retorno->saida_id;

                // Faça uma consulta no banco de dados para obter a nota fiscal correspondente
                $saida = new Saida($saida_id);
                $nota_fiscal = $saida->nota_fiscal;
                $qtd_disponivel = $saida->quantidade;

                $produto_id = $retorno->produto_id;
                $produto_nome = $retorno->produto->nome;
                $data = $retorno->data_retorno;
                $cliente = $retorno->cliente_id;
                $qtd = $retorno->quantidade;
                $this->form->setData($retorno);
                $this->form->getField('quantidade')->setEditable(false);
                $this->form->getField('saida_id')->setEditable(false);

                // Use a função date_format para formatar a data
                $data = date_format(date_create($retorno->data_retorno), 'd/m/Y');

                // Configure o campo de data com a máscara 'dd/mm/yyyy'
                $dataField = $this->form->getField('data_retorno');
                $dataField->setMask('dd/mm/yyyy');

                $novoValor = $saida->valor_total;

                TTransaction::close();

                // Preencha os campos do formulário com os valores obtidos
                TScript::create("document.getElementById('form_nota_fiscal').value = '{$nota_fiscal}';");
                TScript::create("document.getElementById('form_quantidade_disponivel').value = '{$qtd_disponivel}';");
                TScript::create("document.getElementById('form_produto').value = '{$produto_id}';");
                TScript::create("document.getElementById('form_produto_nome').value = '{$produto_nome}';");
                TScript::create("document.getElementById('form_data').value = '{$data}';");
                TScript::create("document.getElementById('form_cliente').value = '{$cliente}';");
                TScript::create("document.getElementById('form_quantidade').value = '{$qtd}';");

                TScript::create("document.getElementById('form_valor_disponivel').value = '{$novoValor}';");
                $this->isAtualizado = true;
            } else {
                // Lida com a situação em que 'key' não está definida, por exemplo, exibir uma mensagem de erro
                error_log('Chave primária ausente.');
            }


            // Resto do código para editar o registro
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    // Método fechar
    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
