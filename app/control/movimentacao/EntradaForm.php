<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
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

class EntradaForm extends TPage
{
    private $form;
    private $isAtualizado = 0;

    use Adianti\base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();


        parent::setTargetContainer('adianti_right_panel');
        $this->setAfterSaveAction(new TAction(['EntradaList', 'onReload'], ['register_state' => 'true']));

        $this->setDatabase('sample');
        $this->setActiveRecord('Entrada');

        // Cria um array com as opções de escolha


        // Criação do formulário
        $this->form = new BootstrapFormBuilder('form_entrada');
        $this->form->setFormTitle('Adicionar ao Estoque');
        $this->form->setClientValidation(true);
        $this->form->setColumnClasses(3, ['col-sm-4', 'col-sm-4', 'col-sm-4']);



        // Criação de fields
        $id = new TEntry('id');
        $produto = new TDBSeekButton('produto_id', 'sample', 'form_entrada', 'Produto', 'id');
        $produto_nome = new TEntry('nome');
        $produto->setDisplayMask('{nome} - {descricao}');
        $produto->setDisplayLabel('Nome do Produto');
        $produto->setAuxiliar($produto_nome);

        $data    = new TDate('data_entrada');
        $data->setId('form_data'); // Defina o ID como 'form_valor_total'

        $fornecedor = new TDBCombo('fornecedor_id', 'sample', 'Fornecedor', 'id', 'nome');
        $nf = new TEntry('nota_fiscal');
        $valor       = new TEntry('preco_unit');
        $tp_entrada       = new TDBCombo('tp_entrada', 'sample', 'Tipo_Entrada', 'id', 'nome');

        $valor = new TEntry('preco_unit');
        $valor->setProperty('onkeyup', 'calcularValorTotal()'); // Adicione esta linha
        $valor->setId('form_preco_unit'); // Defina o ID como 'form_valor_total'

        $qtd = new TEntry('quantidade');
        $qtd->setId('form_quantidade'); // Defina o ID como 'form_valor_total'
        $qtd->setProperty('onkeyup', 'calcularValorTotal()'); // Adicione esta linha


        // Campo de total
        $total = new TEntry('valor_total');
        $total->setId('form_valor_total'); // Defina o ID como 'form_valor_total'
        $total->setProperty('onkeyup', 'calcularValorTotal()'); // Adicione esta linha



        // Adicione fields ao formulário
        $this->form->addFields([new TLabel('Codigo')], [$id],);
        $this->form->addFields([new TLabel('Produto')], [$produto]);
        $this->form->addFields([new TLabel('Fornecedor')], [$fornecedor], [new TLabel('Nota Fiscal')], [$nf]);
        $this->form->addFields([new TLabel('Data de Entrega')], [$data], [new TLabel('Tipo')], [$tp_entrada]);
        $this->form->addFields();
        $this->form->addFields([new TLabel('Quantidade')], [$qtd], [new TLabel('Valor unidade')], [$valor], [new TLabel('Valor Total')], [$total]);
        $this->form->addFields();
        $this->form->addFields();

        // Validação do campo Nome
        $produto->addValidation('Produto', new TRequiredValidator);
        $data->addValidation('Data de Entrega', new TRequiredValidator);
        $fornecedor->addValidation('Fornecedor', new TRequiredValidator);
        $tp_entrada->addValidation('Tipo de Entrada', new TRequiredValidator);
        $qtd->addValidation('Quantidade', new TRequiredValidator);
        $valor->addValidation('Total', new TRequiredValidator);
        $nf->addValidation('Nota Fiscal', new TRequiredValidator);

        // Tornar o campo ID não editável
        $id->setEditable(false);

        // Tamanho dos campos
        $id->setSize('100%');
        $produto->setSize('45%');
        $produto_nome->style .= ';margin-left:3px';
        $produto_nome->setSize('50%');
        $fornecedor->setSize('100%');
        $fornecedor->enableSearch();
        $data->setMask('dd/mm/yyyy');
        $data->setDatabaseMask('yyyy-mm-dd');
        $nf->setNumericMask(2, '', '', true);
        $valor->setSize('100%');
        $valor->setNumericMask(2, '.', '', true);
        $total->setSize('100%');

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

    public function onSave($param)
    {
        try {
            TTransaction::open('sample');

            // Verifica se é uma edição de registro existente
            $isEditingExistingRecord = !empty($param['id']);

            // Salvar entrada
            $entrada = new Entrada();
            $entrada->fromArray($param);
            $data_entrada = DateTime::createFromFormat('d/m/Y', $entrada->data_entrada);

            if (!$isEditingExistingRecord) {
                // Apenas defina o created_at se for um novo registro
                $entrada->created_at = date('Y-m-d H:i:s');
            }

            if ($data_entrada) {
                $entrada->data_entrada = $data_entrada->format('Y-m-d');
            }

            $entrada->store();

            // Atualizar o mapa de estoque somente se não for uma edição de registro existente
            if (!$isEditingExistingRecord) {
                $produto_id = $entrada->produto_id;
                $quantidade = $entrada->quantidade;
                $valor = $entrada->preco_unit;

                // Verifique se já existe uma entrada no mapa de estoque para esse produto
                $mapaEstoque = Estoque::where('produto_id', '=', $produto_id)->load();

                if ($mapaEstoque) {
                    $mapaEstoque = $mapaEstoque[0];

                    $valorTotal = $mapaEstoque->valor_total;
                    $QuantidadeTotal = $mapaEstoque->quantidade;


                    $mapaEstoque->quantidade += $quantidade;
                    $mapaEstoque->quant_retirada += $quantidade;
                    $mapaEstoque->updated_at = date('Y-m-d H:i:s');

                    $mediaPonderadaEstoque = ($entrada->valor_total + $valorTotal) / ($quantidade + $QuantidadeTotal);
                    $mapaEstoque->valor_total = $mapaEstoque->quantidade * $mediaPonderadaEstoque;
                    $mapaEstoque->preco_unit = $mediaPonderadaEstoque;
                    $mapaEstoque->store();
                } else {
                    $mapaEstoque = new Estoque();
                    $mapaEstoque->produto_id = $entrada->produto_id;
                    $mapaEstoque->quantidade = $quantidade;
                    $mapaEstoque->preco_unit = $valor;
                    $mapaEstoque->valor_total = $entrada->valor_total;
                    $mapaEstoque->entrada_id = $entrada->id;
                    $mapaEstoque->fornecedor_id = $entrada->fornecedor_id;
                    $mapaEstoque->nota_fiscal = $entrada->nota_fiscal;
                    $mapaEstoque->quant_retirada = $entrada->quantidade;
                    $mapaEstoque->created_at = date('Y-m-d H:i:s');
                    $mapaEstoque->store();
                }
            }

            $this->createMovement($entrada);

            TTransaction::close();
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $this->afterSaveAction);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }


    private function createMovement($entrada)
    {
        try {
            //GRAVANDO MOVIMENTAÇÃO
            $mov = new Movimentacoes();
            $usuario_logado = TSession::getValue('userid');
            $desc = $entrada->tipo->nome.' - '. $entrada->fornecedor->nome;
            $descricao = substr($desc, 0, 30) . '...'; 
            $mov->data_hora = date('Y-m-d H:i:s');;
            $mov->tipo = $entrada->tipo->nome;
            $mov->descricao = $descricao;
            $mov->valor_total = $entrada->valor_total;
            $mov->produto_id = $entrada->produto_id;
            $mov->responsavel_id = $usuario_logado;
            $mov->quantidade = $entrada->quantidade;

            $estoque = Estoque::where('produto_id', '=', $entrada->produto_id)->first();
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
    public  function onEdit($param)
    {
        try {

            if (isset($param['key'])) {

                TTransaction::open('sample');
                $key = $param['key']; // A chave primária do registro que está sendo editado
                $entrada = new Entrada($key);

                $this->form->setData($entrada);
                $this->form->getField('quantidade')->setEditable(false);
                $this->form->getField('produto_id')->setEditable(false);
                $this->form->getField('preco_unit')->setEditable(false);
                $this->form->getField('valor_total')->setEditable(false);
                $this->form->getField('fornecedor_id')->setEditable(false);
                $this->form->getField('tp_entrada')->setEditable(false);
                $this->form->getField('nota_fiscal')->setEditable(false);
                $this->form->getField('data_entrada')->setEditable(false);
                $alert = new TAlert('warning', 'Não é possível editar esta entrada, pois já existem vinculações.');
                $alert->show();
                // Use a função date_format para formatar a data
                $data = date_format(date_create($entrada->data_entrada), 'd/m/Y');

                // Configure o campo de data com a máscara 'dd/mm/yyyy'
                $dataField = $this->form->getField('data_entrada');
                $dataField->setMask('dd/mm/yyyy');

                // Verifique se o formulário está em modo de edição
                if (!$this->isAtualizado) {
                    // Execute o cálculo apenas se o formulário não tiver sido atualizado antes
                    TScript::create("document.getElementById('form_data').value = '{$data}';");
                }


                TTransaction::close();

                $this->isAtualizado = 1;
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
