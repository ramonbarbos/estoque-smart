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
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TAlert;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TToast;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TCheckList;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TFormSeparator;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Form\TSpinner;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class RetornoClienteForm extends TPage
{
    private $form;
    private $isAtualizado = 0;

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
        $this->form->setFormTitle('Estornar Baixa');
        $this->form->setClientValidation(true);
        //$this->form->setColumnClasses(2, ['col-sm-4', 'col-sm-4', 'col-sm-4']);



        // Criação de fields
        $id = new TEntry('id');
        $criteria_saida = new TCriteria();
        $criteria_saida->add(new TFilter('status', '=', 1));
        $saida       = new TDBUniqueSearch('saida_id', 'sample', 'Saida', 'id', 'id', null, $criteria_saida);
        $saida->setMask('Baixa: {id} ');
        $saida->setChangeAction(new TAction([$this, 'onSaidaChange']));

        $dt_retorno    = new TDate('data_retorno');
        $data    = new TDate('data_saida');
        $data->setId('form_data');
        $tp_saida       = new TDBCombo('tp_saida', 'sample', 'Tipo_Saida', 'id', 'nome');
        $cliente = new TDBCombo('cliente_id', 'sample', 'Cliente', 'id', 'nome');
        $obs = new TText('motivo');
        //-------------------------------------------------------------------------------------

        //-------------------------------------------------------------------------------------
        $uniqid      = new THidden('uniqid');
        $detail_id         = new THidden('detail_id');

        $criteria_prod = new TCriteria();
        $criteria_prod->add(new TFilter('quantidade', '>', 0));
        $produto_id = new TEntry('produto_id');
        // $produto_id->setChangeAction(new TAction([$this, 'onProductChange']));
        //  $produto_id->setMask('{produto->nome}');
        $preco_unit      = new TEntry('preco_unit');
        $quantidade     = new THidden('quantidade');
        $quantidade_retorno     = new TEntry('quantidade_retorno');


        // Validação do campo 
        $data->addValidation('Data', new TRequiredValidator);
        $cliente->addValidation('Cliente', new TRequiredValidator);
        $tp_saida->addValidation('Tipo', new TRequiredValidator);
        $obs->addValidation('Motivo', new TRequiredValidator);
        $dt_retorno->addValidation('Retorno', new TRequiredValidator);



        $id->setEditable(false);
        $id->setSize('100%');
        $saida->setSize('100%');
        $saida->setMinLength(0);
        $cliente->setSize('100%');
        $cliente->setEditable(false);
        $tp_saida->setEditable(false);
        $tp_saida->setSize('100%', 80);
        $obs->setSize('100%', 80);

        //$produto_id->setMinLength(0);
        $produto_id->setEditable(false);

        $data->setSize('100%');
        $data->setMask('dd/mm/yyyy');
        $data->setDatabaseMask('yyyy-mm-dd');
        $data->setEditable(false);
        $dt_retorno->setMask('dd/mm/yyyy');
        $dt_retorno->setDatabaseMask('yyyy-mm-dd');
        $quantidade_retorno->setNumericMask(2, '.', '', true);
        $preco_unit->setNumericMask(2, '.', '', true);
        $preco_unit->setEditable(false);



        // fildes 
        $this->form->addFields([new TLabel('Codigo')], [$id]);
        $this->form->addFields([new TLabel('Aquisição Baixada (*)', '#FF0000')], [$saida]);
        $this->form->addFields([new TLabel('Data Solic')], [$data], [new TLabel('Retorno (*)', '#FF0000')], [$dt_retorno]);
        $this->form->addFields([new TLabel('Tipo')], [$tp_saida], [new TLabel('Cliente')], [$cliente],);
        $this->form->addFields([new TLabel('Motivo (*)', '#FF0000')], [$obs]);

        // fildes 1 tab
        $subform = new BootstrapFormBuilder;
        $subform->setFieldSizes('100%');
        $subform->setProperty('style', 'border:none');

        $subform->appendPage('Produtos');
        $subform->addFields([$uniqid], [$detail_id], [$quantidade]);
        $subform->addFields([new TLabel('Produto (*)', '#FF0000')], [$produto_id], [new TLabel('Quant. (*)', '#FF0000')], [$quantidade_retorno],);
        $subform->addFields([new TLabel('Preço (*)', '#FF0000')], [$preco_unit]);
        $add_product = TButton::create('add_product', [$this, 'onProductAdd'], 'Register', 'fa:plus-circle green');
        $add_product->getAction()->setParameter('static', '1');

        $this->form->addContent([$subform]);
        $this->form->addFields([], [$add_product]);


        $this->product_list = new BootstrapDatagridWrapper(new TDataGrid);
        $this->product_list->setHeight(150);
        $this->product_list->makeScrollable();
        $this->product_list->setId('products_list');
        $this->product_list->generateHiddenFields();
        $this->product_list->style = "min-width: 700px; width:100%;margin-bottom: 10px";
        $this->product_list->setMutationAction(new TAction([$this, 'onMutationAction']));

        $col_uniq   = new TDataGridColumn('uniqid', 'Uniqid', 'center', '10%');
        $col_id     = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_pid    = new TDataGridColumn('produto_id', 'Cod', 'center', '10%');
        $col_descr  = new TDataGridColumn('produto_id', 'Produto', 'left', '30%');
        $col_quantidade = new TDataGridColumn('quantidade', 'Quantidade', 'left', '10%');
        $col_quantidade_retorno = new TDataGridColumn('quantidade_retorno', 'Quant. Retorno', 'left', '10%');
        $col_price  = new TDataGridColumn('preco_unit', 'Preço', 'right', '15%');
        $col_subt   = new TDataGridColumn('={quantidade_retorno} * {preco_unit} ', 'Subtotal', 'right', '20%');


        $this->product_list->addColumn($col_uniq);
        $this->product_list->addColumn($col_id);
        $this->product_list->addColumn($col_pid);
        $this->product_list->addColumn($col_descr);
        $this->product_list->addColumn($col_quantidade);
        $this->product_list->addColumn($col_quantidade_retorno);
        $this->product_list->addColumn($col_price);
        $this->product_list->addColumn($col_subt);

        $col_descr->setTransformer(function ($value) {
            return Produto::findInTransaction('sample', $value)->descricao;
        });

        $col_subt->enableTotal('sum', 'R$', 2, ',', '.');

        $col_id->setVisibility(false);
        $col_uniq->setVisibility(false);


        // creates two datagrid actions
        $action1 = new TDataGridAction([$this, 'onEditItemProduto']);
        $action1->setFields(['uniqid', '*']);

        $action2 = new TDataGridAction([$this, 'onDeleteItem']);
        $action2->setField('uniqid');

        // add the actions to the datagrid
        $this->product_list->addAction($action1, _t('Edit'), 'far:edit blue');
        //$this->product_list->addAction($action2, _t('Delete'), 'far:trash-alt red');

        $this->product_list->createModel();

        $panel = new TPanelGroup();
        $panel->add($this->product_list);
        $panel->getBody()->style = 'overflow-x:auto';
        $this->form->addContent([$panel]);

        $format_value = function ($value) {
            if (is_numeric($value)) {
                return 'R$ ' . number_format($value, 4, ',', '.');
            }
            return $value;
        };

        $col_price->setTransformer($format_value);
        $col_subt->setTransformer($format_value);




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
    public static function onSaidaChange($params)
    {
        if (!empty($params['saida_id'])) {
            try {
                $saida_id = $params['saida_id'];
                TTransaction::open('sample');
                $saida = new Saida($saida_id);
                $saida_items = Item_Saida::where('saida_id', '=', $saida_id)->load();

                $formInstance = new self;
                $formInstance->form->getField('tp_saida')->setEditable(false);
                $formInstance->form->getField('cliente_id')->setEditable(false);
                //$formInstance->form->getField('preco_unit')->setEditable(false);

                foreach ($saida_items as $item) {
                    $item->uniqid = uniqid();
                    $row = $formInstance->product_list->addItem((object) $item);
                    $row->id = $item->uniqid;
                    TDataGrid::replaceRowById('products_list', $item->uniqid, $row);
                }

                $saida->id     = '';
                TForm::sendData('form_retorno', (object) $saida);
                TTransaction::close();
            } catch (Exception $e) {
                new TMessage('error', $e->getMessage());
                TTransaction::rollback();
            }
        }
    }


    public static function onProductChange($params)
    {
        if (!empty($params['produto_id'])) {
            try {
                $produto_id = $params['produto_id'];
                TTransaction::open('sample');
                $estoque   = Estoque::where('produto_id', '=', $produto_id)->first();
                TForm::sendData('form_retorno', (object) ['preco_unit' => $estoque->preco_unit]);
                TTransaction::close();
            } catch (Exception $e) {
                new TMessage('error', $e->getMessage());
                TTransaction::rollback();
            }
        }
    }
    public function onProductAdd($param)
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();

            if ((!$data->produto_id) || (!$data->quantidade) || (!$data->preco_unit)) {
                throw new Exception('Para incluir é necessario informar o produto.');
            }

            $uniqid = !empty($data->uniqid) ? $data->uniqid : uniqid();

            $grid_data = [
                'uniqid'      => $uniqid,
                'id'          => $data->detail_id,
                'produto_id'  => $data->produto_id,
                'quantidade'      => $data->quantidade,
                'quantidade_retorno'      => $data->quantidade_retorno,
                'preco_unit'  => $data->preco_unit,

            ];

            // insert row dynamically
            $row = $this->product_list->addItem((object) $grid_data);
            $row->id = $uniqid;

            TDataGrid::replaceRowById('products_list', $uniqid, $row);

            // clear product form fields after add
            $data->uniqid     = '';
            $data->detail_id         = '';
            $data->produto_id = '';
            $data->product_detail_name       = '';
            $data->quantidade     = '';
            $data->quantidade_retorno     = '0';
            $data->preco_unit      = '';
            // $data->product_detail_discount   = '';

            // send data, do not fire change/exit events
            TForm::sendData('form_retorno', $data, false, false);
        } catch (Exception $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }
    public static function onEditItemProduto($param)
    {
        $data = new stdClass;
        $data->uniqid     = $param['uniqid'];
        $data->detail_id         = $param['id'];
        $data->produto_id = $param['produto_id'];
        $data->quantidade     = $param['quantidade'];
        $data->quantidade_retorno     = 0;
        $data->preco_unit      = $param['preco_unit'];
        //$data->product_detail_discount   = $param['discount'];

        // send data, do not fire change/exit events
        TForm::sendData('form_retorno', $data, false, false);
    }

    /**
     * Delete a product from item list
     * @param $param URL parameters
     */
    public static function onDeleteItem($param)
    {
        $data = new stdClass;
        $data->uniqid     = '';
        $data->detail_id         = '';
        $data->produto_id = '';
        $data->quantidade     = '';
        $data->preco_unit      = '';
        //$data->product_detail_discount   = '';

        // send data, do not fire change/exit events
        TForm::sendData('form_retorno', $data, false, false);

        // remove row
        TDataGrid::removeRowById('products_list', $param['uniqid']);
    }
    public function onEdit($param)
    {
        try {
            TTransaction::open('sample');

            if (isset($param['key'])) {
                $key = $param['key'];

                $retorno = new Retorno_Cliente($key);
                $saida = new Saida($retorno->saida_id);
                $retorno_itens = Item_Retorno_Cliente::where('retorno_id', '=', $retorno->id)->load();
                $this->form->getField('produto_id')->setEditable(false);
                $this->form->getField('quantidade')->setEditable(false);
                $this->form->getField('preco_unit')->setEditable(false);

                if ($retorno->status == 0) {
                    $alert = new TAlert('warning', 'Estorno foi cancelado.');
                    $alert->show();
                }

                foreach ($retorno_itens as $item) {
                    $item->uniqid = uniqid();
                    $row = $this->product_list->addItem($item);
                    $row->id = $item->uniqid;
                }
                $this->form->setData($retorno,);
                $this->form->setData($saida);
                TTransaction::close();
            } else {
                $this->form->clear();
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

            $data = $this->form->getData();
            $this->form->validate();

            $retorno = new Retorno_Cliente();
            $retorno->fromArray((array) $data);

            if ($this->hasNegativeValues($param['products_list_quantidade_retorno']) || $this->hasNegativeValues($param['products_list_preco_unit'])) {
                throw new Exception('Não é permitido inserir valores negativos em quantidade ou preço unitário.');
            }

            if (!empty($retorno->id)) {
                new TMessage('warning', 'Este Estorno já foi salvo.');
            } else {
                $retorno->store();

                $total = 0;

                if (!empty($param['products_list_produto_id'])) {
                    foreach ($param['products_list_produto_id'] as $key => $item_id) {
                        $item = new Item_Retorno_Cliente;
                        $item->produto_id  = $item_id;
                        $item->preco_unit  = (float) $param['products_list_preco_unit'][$key];
                        $item->quantidade_retorno  = (float) $param['products_list_quantidade_retorno'][$key];
                        $item->quantidade  = (float) $param['products_list_quantidade'][$key];
                        $item->total       =  $item->preco_unit * $item->quantidade_retorno;
                        $item->retorno_id  = $retorno->id;

                        $itemSaida = Item_Saida::where('saida_id', '=', $retorno->saida_id)
                            ->where('produto_id', '=', $item->produto_id)
                            ->first();

                        if (!$itemSaida || $item->quantidade_retorno > $itemSaida->quantidade) {
                            $delete = new Retorno_Cliente($retorno->id);
                            $delete->delete();
                            throw new Exception('Não é permitido inserir valores maiores que a quantidade baixada ou o item não existe na saída.');
                        }
                        if(empty($item->quantidade_retorno )){
                            $delete = new Retorno_Cliente($retorno->id);
                            $delete->delete();
                            throw new Exception('Voce precisa informar a quantidade de estorno nos produtos.');
                        }


                        $item->store();
                        $total += $item->total;

                        $this->insertEstoque($item, $item->total, $item->quantidade_retorno);
                        $this->createMovement($item);

                    }
                }

                $this->atualizarQuantidadeTotalSaida($retorno );
                $this->atualizarStatusSaida($retorno );
                $retorno->valor_total = $total;
                $retorno->store();

                TForm::sendData('form_retorno', (object) ['id' => $retorno->id]);
                new TMessage('info', 'Registos Salvos.', $this->afterSaveAction);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
            TTransaction::rollback();
        }
    }

    private function atualizarQuantidadeTotalSaida($info)
    {
        $saida = new Saida($info->saida_id);
        $quantidadeTotalOriginal = $saida->quantidade_total;
        
    
        $itensRetorno = Item_Retorno_Cliente::where('retorno_id', '=', $info->id)->load();
        $novaQuantidadeTotal = $quantidadeTotalOriginal;
        
        foreach ($itensRetorno as $itemRetorno) {
            $novaQuantidadeTotal -= $itemRetorno->quantidade_retorno;
        }
        
        $saida->quantidade_total = $novaQuantidadeTotal;
        $saida->store();
    }

    private function atualizarStatusSaida($info)
    {
        $saida = new Saida($info->saida_id);
        $quantidadeTotal = $saida->quantidade_total;
    
        if ($quantidadeTotal == 0) {
            $saida->status = 2;  
            $saida->store();
        }
    }

    private function insertEstoque($item, $total, $quantidade)
    {
        try {
            TTransaction::open('sample');

            // Buscar o estoque existente para o produto
            $estoque = Estoque::where('produto_id', '=', $item->produto_id)->first();

            // Calcular a média ponderada
            $mediaPonderadaEstoque = 0;
            if ($estoque) {
                $mediaPonderadaEstoque = ($estoque->valor_total + $total) / ($estoque->quantidade + $quantidade);
            } else {
                $mediaPonderadaEstoque = $item->preco_unit;
            }

            // Atualizar ou inserir o registro de estoque
            if ($estoque) {
                if ($estoque->quantidade != $quantidade || $estoque->preco_unit != $item->preco_unit) {

                    $estoque->quantidade += $quantidade;
                    $estoque->preco_unit = $mediaPonderadaEstoque;
                    $estoque->valor_total = $estoque->quantidade * $mediaPonderadaEstoque;
                }
            } else {
                // O produto não existe no estoque, insira um novo registro
                $estoque = new Estoque;
                $estoque->produto_id = $item->produto_id;
                $estoque->quantidade = $quantidade;
                $estoque->preco_unit = $mediaPonderadaEstoque;
                $estoque->valor_total = $quantidade * $mediaPonderadaEstoque;
            }

            $estoque->store();
            TTransaction::close();
        } catch (Exception $e) {
            // Tratar erros aqui, se necessário
            TTransaction::rollback();
            throw new Exception("Erro ao atualizar o estoque: " . $e->getMessage());
        }
    }
    public static function onMutationAction($param)
    {

        $total = 0;

        if ($param['list_data']) {
            foreach ($param['list_data'] as $row) {
                $total +=  floatval($row['preco_unit'])  *  floatval($row['quantidade']);
            }
        }

        TToast::show('info', 'Novo total: <b>' . 'R$ ' . number_format($total, 2, ',', '.') . '</b>', 'bottom right');
        TToast::show('info', 'Clique no produto para editar.');
    }

    private function hasNegativeValues($array)
    {
        foreach ($array as $value) {
            if ((float) $value < 0) {
                return true;
            }
        }
        return false;
    }

    private function createMovement($info)
    {
        try {
            TTransaction::open('sample');
            //GRAVANDO MOVIMENTAÇÃO
            $mov = new Movimentacoes();
            $saida = new Saida($info->saida_id);
            $usuario_logado = TSession::getValue('userid');
            $desc =  'Estorno de Baixa';
            //$descricao = substr($desc, 0, 30) . '...';
            $mov->data_hora = date('Y-m-d H:i:s');
            $mov->descricao = $desc;
            $mov->preco_unit = $info->preco_unit;
            $mov->produto_id = $info->produto_id;
            $mov->responsavel_id = $usuario_logado;
            $mov->quantidade = $info->quantidade_retorno;

            $estoque = Estoque::where('produto_id', '=', $info->produto_id)->first();
            if ($estoque->valor_total > 0) {
                $mov->saldo_anterior = $estoque->valor_total;
            } else {
                $mov->saldo_anterior = 0;
            }
            $mov->store();
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
