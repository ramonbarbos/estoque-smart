<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TAlert;
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
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class SaidaForm extends TPage
{
    private $form;
    private $isAtualizado = false;

    use Adianti\base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        parent::setTargetContainer('adianti_right_panel');
        $this->setAfterSaveAction(new TAction(['SaidaList', 'onReload'], ['register_state' => 'true']));

        $this->setDatabase('sample');
        $this->setActiveRecord('Saida');



        // Criação do formulário
        $this->form = new BootstrapFormBuilder('form_saida');
        $this->form->setFormTitle('Retirada');
        $this->form->setClientValidation(true);
        $this->form->setColumnClasses(2, ['col-sm-5 col-lg-4', 'col-sm-7 col-lg-8']);


        try {
            TTransaction::open('sample');
        
                $criteria = new TCriteria;
                $criteria->add(new TFilter('quant_retirada', '>', 0));
              //  $criteria->add(new TFilter('produto_id', '>', 0));
                $criteria->setProperty('order', 'id');
                // Use o critério para buscar os registros do banco de dados
                $repository = new TRepository('Estoque');
                $items = $repository->load($criteria);

                // Crie um array de itens no formato chave-valor
                $options = [];
                foreach ($items as $item) {
                    $options[$item->produto->id] = $item->produto->nome;
                }

            TTransaction::close(); // Feche a transação quando terminar
        } catch (Exception $e) {
            // Lida com exceções aqui, como TTransaction não pode ser aberto
            echo 'Erro: ' . $e->getMessage();
            TTransaction::rollback(); // Se ocorrer um erro, faça rollback na transação
        }

        // Criação de fields
        $id = new TEntry('id');
        $produto = new TDBCombo('prod', 'sample', 'Produto', 'id', 'nome');
        $produto->setChangeAction(new TAction([$this, 'onProdutoChange']));
  
        $produto_id = new TEntry('produto_id');
        $produto_id->setId('form_produto');

        $estoque_id = new TEntry('estoque_id');
        $estoque_id->setId('form_estoque');
   

        $datas    = new TDate('data_saida');
        $datas->setId('form_data');
        $cliente = new TDBCombo('cliente_id', 'sample', 'Cliente', 'id', 'nome');
        $cliente->setId('form_cliente');
        $nf = new TEntry('nota_fiscal');
        $nf->setId('form_nota_fiscal');
        $qtd_disponivel = new TEntry('quantidade_disponivel');
        $qtd_disponivel->setId('form_quantidade_disponivel');
        $valor_disponivel = new TEntry('valor_disponivel');
        $valor_disponivel->setId('form_valor_disponivel');
        $tp_saida       = new TDBCombo('tp_saida', 'sample', 'Tipo_Saida', 'id', 'nome');
        $tp_saida->setId('form_tp_saida');
        $valor = new TEntry('preco_unit');
        $valor->setProperty('onkeyup', 'calcularValorTotal()');
        $valor->setId('form_preco_unit');
        $qtd = new TEntry('quantidade');
        $qtd->setId('form_quantidade');
        $qtd->setProperty('onkeyup', 'calcularValorTotal()');
        $total = new TEntry('valor_total');
        $total->setId('form_valor_total');
        $total->setProperty('onkeyup', 'calcularValorTotal()');

        $produto->addItems($options);

        // Adicione fields ao formulário
        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Produto')], [$produto, $produto_id,$estoque_id]);
        $this->form->addFields([new TLabel('Nota Fiscal')], [$nf]);
        $this->form->addFields([new TLabel('Data de Saida')], [$datas]);
        $this->form->addFields([new TLabel('Cliente')], [$cliente]);
        $this->form->addFields([new TLabel('Tipo de Saida')], [$tp_saida]);
        $this->form->addFields([new TLabel('Quantidade Disponível')], [$qtd_disponivel]);
        $this->form->addFields([new TLabel('Quantidade')], [$qtd]);
        $this->form->addFields([new TLabel('Valor Disponível')], [$valor_disponivel]);
        $this->form->addFields([new TLabel('Valor unidade')], [$valor]);
        $this->form->addFields([new TLabel('Total')], [$total]);

        // Validação do campo Nome
        $produto->addValidation('Produto', new TRequiredValidator);
        $cliente->addValidation('Cliente', new TRequiredValidator);
        $datas->addValidation('Data de Saida', new TRequiredValidator);
        $tp_saida->addValidation('Tipo de Saida', new TRequiredValidator);

        // Tornar o campo ID não editável
        $id->setEditable(false);
        $nf->setEditable(false);
        $qtd_disponivel->setEditable(false);
        $valor_disponivel->setEditable(false);
        $valor->setEditable(false);
        $estoque_id->style = 'display: none;';
        // Tamanho dos campos
        $id->setSize('100%');
        $produto->setSize('50%');
        $produto->enableSearch();
        $cliente->setSize('100%');
        $cliente->enableSearch();
        //$datas->setDatabaseMask('yyyy-mm-dd');
        $datas->setMask('dd/mm/yyyy');
        $nf->setNumericMask(2, '', '', true);
        // $qtd_disponivel->setRange(0, 100, 1);
        $qtd_disponivel->setValue('0');
        $qtd->setValue('0');
        $valor_disponivel->setNumericMask(4, ',', '.', true);
        $valor_disponivel->setValue('0,00');
        $valor->setSize('100%');
        $valor->setValue('0,00');
        $total->setValue('0,00');
        //$total->setNumericMask(4, ',', '.', false);


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

            if (isset($param['prod'])) {
                $produto_id = $param['prod'];
                $estoque = Estoque::where('produto_id', '=', $produto_id)->first();
                // Faça uma consulta no banco de dados para obter a nota fiscal correspondente
                 //  $estoque = new Estoque($estoque_id);
                $nota_fiscal = $estoque->nota_fiscal;
                $produto = $estoque->produto_id;
                $estoque_id = $estoque->id;
                $qtd_disponivel = $estoque->quantidade; // Suponhamos que o campo no banco de dados se chama "quantidade"
                $valor_disponivel = $estoque->valor_total; // Suponhamos que o campo no banco de dados se chama "valor"
                $valor = $estoque->preco_unit; // Suponhamos que o campo no banco de dados se chama "valor"

                TTransaction::close();

                // Preencha o campo de Nota Fiscal com o valor obtido
                TScript::create("document.getElementById('form_nota_fiscal').value = '{$nota_fiscal}';");
                TScript::create("document.getElementById('form_quantidade_disponivel').value = '{$qtd_disponivel}';");
                TScript::create("document.getElementById('form_valor_disponivel').value = '{$valor_disponivel}';");
                TScript::create("document.getElementById('form_preco_unit').value = '{$valor}';");
                TScript::create("document.getElementById('form_produto').value = '{$produto}';");
                TScript::create("document.getElementById('form_estoque').value = '{$estoque_id}';");
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

            $saida = new Saida;
            $saida->fromArray($param);

            $data_saida = DateTime::createFromFormat('d/m/Y', $saida->data_saida);
            if ($data_saida) {
                $saida->data_saida = $data_saida->format('Y-m-d');
            }
            $saida->quant_retirada =  $saida->quantidade;

            $existente = Saida::where('id', '=', $saida->id)
                ->count();


            if ($existente == 0 ) {
                $saida->store();
                // Recupera o ID da saída após a inserção no banco de dados
            $saida_id = $saida->id;

            // Atualiza o estoque com o ID da saída
            $this->atualizarEstoque($saida->estoque_id, $saida->quantidade, $saida_id);


                TTransaction::close();
            } else {
                $saida->store(); // Salva a saída no banco de dados

            }



            TTransaction::close();
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $this->afterSaveAction);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function atualizarEstoque($entrada_id, $quantidadeVendida, $saida_id)
    {
        try {
            TTransaction::open('sample');

            $estoque = new Estoque($entrada_id);
            $estoque->saida_id = $saida_id;


            // Verifica se a quantidade em estoque é maior ou igual à quantidade vendida
            if ($estoque->quantidade >= $quantidadeVendida && !$this->estoqueAtualizado) {
                // Calcula a nova quantidade em estoque após a venda
                $novaQuantidade = $estoque->quantidade - $quantidadeVendida;
                $valorVendido = $estoque->preco_unit * $quantidadeVendida;
                $novoValor = $estoque->valor_total - $valorVendido;
                $estoque->valor_total = $novoValor;
                

                // Atualiza a quantidade em estoque no objeto
                $estoque->quantidade =  $estoque->quant_retirada - $quantidadeVendida;
                $estoque->quant_retirada =  $estoque->quant_retirada - $quantidadeVendida;

                // Atualiza o registro no banco de dados
                $estoque->store();
                // Atualiza o campo de quantidade disponível no formulário usando JavaScript
                TScript::create("document.getElementById('form_quantidade_disponivel').value = '{$novaQuantidade}';");
                /// TScript::create("document.getElementById('form_valor_total).value = '{$novoValor}';");
                $this->estoqueAtualizado = true;
            } elseif ($estoque->quantidade < $quantidadeVendida) {
                throw new Exception('Quantidade solicitada atingiu o limite do estoque.');
                TTransaction::close();
            } else {
                // Se a quantidade em estoque for insuficiente, você pode lançar uma exceção ou tratar de acordo com sua lógica de negócios
                TTransaction::rollback();
                throw new Exception('Quantidade em estoque insuficiente.');
            }

            // Comita a transação após o sucesso
            TTransaction::close();
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
                $saida = new Saida($key);

                $estoque_id = $saida->estoque_id;

                // Faça uma consulta no banco de dados para obter a nota fiscal correspondente
                $estoque = new Estoque($estoque_id);
                $qtd_disponivel = $estoque->quantidade;
                $produto = $saida->produto_id;
                $produto_id = $saida->produto_id;
                $data = $saida->data_saida;
                $nota_fiscal = $saida->nota_fiscal;
                $cliente = $saida->cliente_id;
                $tp_saida = $saida->tp_saida;
                $qtd = $saida->quantidade;
                $this->form->setData($saida);
                $this->form->getField('prod')->setEditable(false);
                $this->form-> getField('prod')->setValue($produto_id);
                $this->form->getField('produto_id')->setEditable(false);
                $this->form->getField('quantidade')->setEditable(false);


                
                $retorno = Retorno_Cliente::where('saida_id', '=', $saida->id)
                ->first();
               
                if($retorno){
                    $this->form->getField('quantidade')->setEditable(false);
                    $this->form->getField('data_saida')->setEditable(false);
                    $this->form->getField('cliente_id')->setEditable(false);
                    $this->form->getField('tp_saida')->setEditable(false);

                    $alert = new TAlert('warning', 'Não é possível editar esta saida, pois já existem vinculações.');
                    $alert->show();
                }

                // Use a função date_format para formatar a data
                $data = date_format(date_create($saida->data_saida), 'd/m/Y');
               
                // Configure o campo de data com a máscara 'dd/mm/yyyy'
                $dataField = $this->form->getField('data_saida');
                $dataField->setMask('dd/mm/yyyy');

                $novoValor = $estoque->valor_total;

                TTransaction::close();

                

                // Preencha os campos do formulário com os valores obtidos
                TScript::create("document.getElementById('form_nota_fiscal').value = '{$nota_fiscal}';");
                TScript::create("document.getElementById('form_quantidade_disponivel').value = '{$qtd_disponivel}';");
                TScript::create("document.getElementById('form_produto').value = '{$produto}';");
                TScript::create("document.getElementById('form_data').value = '{$data}';");
                TScript::create("document.getElementById('form_cliente').value = '{$cliente}';");
                TScript::create("document.getElementById('form_tp_saida').value = '{$tp_saida}';");
                TScript::create("document.getElementById('form_quantidade').value = '{$qtd}';");

                TScript::create("document.getElementById('form_valor_disponivel').value = '{$novoValor}';");
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