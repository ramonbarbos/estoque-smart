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
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
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
        $this->form->setColumnClasses(2, ['col-sm-4', 'col-sm-4', 'col-sm-4']);



        // Criação de fields
        $id = new TEntry('id');
        $data    = new TDate('data_entrada');
        $data->setId('form_data'); // Defina o ID como 'form_valor_total'
        $tp_entrada       = new TDBCombo('tp_entrada', 'sample', 'Tipo_Entrada', 'id', 'nome');
        $fornecedor = new TDBCombo('fornecedor_id', 'sample', 'Fornecedor', 'id', 'nome');
        //-------------------------------------------------------------------------------------
        $nf = new TEntry('nota_fiscal');
        $nf_serie = new TEntry('serie_notaFiscal');
        $dt_nf = new TDate('dt_notaFiscal');
        $nf_total = new TEntry('valor_total');
        //-------------------------------------------------------------------------------------
        $uniqid      = new THidden('uniqid');
        $detail_id         = new THidden('detail_id');
        $produto_id = new TDBUniqueSearch('produto_id', 'sample', 'Produto', 'id', 'id');
        $produto_id->setMask('{nome} - {descricao}');
        $preco_unit      = new TEntry('preco_unit');
        $quantidade     = new TEntry('quantidade');


        // Validação do campo 
        $data->addValidation('Entrega', new TRequiredValidator);
        $fornecedor->addValidation('Fornecedor', new TRequiredValidator);
        $tp_entrada->addValidation('Tipo', new TRequiredValidator);

        // Validação do campo 
        $nf->addValidation('Nota Fiscal', new TRequiredValidator);
        $nf_serie->addValidation('Serie', new TRequiredValidator);
        $dt_nf->addValidation('Emissão', new TRequiredValidator);

        $id->setEditable(false);
        $id->setSize('100%');
        $fornecedor->setSize('100%');
        $fornecedor->enableSearch();
        $produto_id->setMinLength(0);
        $data->setMask('dd/mm/yyyy');
        $data->setDatabaseMask('yyyy-mm-dd');
        // $nf->setNumericMask(2, '', '', true);
        $dt_nf->setSize('50%');
        $dt_nf->setMask('dd/mm/yyyy');
        $dt_nf->setDatabaseMask('yyyy-mm-dd');
        $quantidade->setNumericMask(2, '.', '', true);
        $preco_unit->setNumericMask(2, '.', '', true);
        // fildes 
        $this->form->addFields([new TLabel('Codigo')], [$id], [new TLabel('Entrega (*)', '#FF0000')], [$data],);
        $this->form->addFields([new TLabel('Tipo (*)', '#FF0000')], [$tp_entrada], [new TLabel('Fornecedor(*)', '#FF0000')], [$fornecedor]);

        // fildes 1 tab
        $subform = new BootstrapFormBuilder;
        $subform->setFieldSizes('100%');
        $subform->setProperty('style', 'border:none');

        $subform->appendPage('Nota Fiscal');
        $subform->addFields([new TLabel('Nº (*)', '#FF0000')], [$nf], [new TLabel('Emissão (*)', '#FF0000')], [$dt_nf],);
        $subform->addFields([new TLabel('Serie  (*)', '#FF0000')], [$nf_serie]);

        //fildes 2 tab
        $subform->appendPage('Produtos');
        $subform->addFields([$uniqid], [$detail_id],);
        $subform->addFields([new TLabel('Produto (*)', '#FF0000')], [$produto_id], [new TLabel('Quant. (*)', '#FF0000')], [$quantidade],);
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
        $col_price  = new TDataGridColumn('preco_unit', 'Preço', 'right', '15%');
        $col_subt   = new TDataGridColumn('={quantidade} * {preco_unit} ', 'Subtotal', 'right', '20%');


        $this->product_list->addColumn($col_uniq);
        $this->product_list->addColumn($col_id);
        $this->product_list->addColumn($col_pid);
        $this->product_list->addColumn($col_descr);
        $this->product_list->addColumn($col_quantidade);
        $this->product_list->addColumn($col_price);
        $this->product_list->addColumn($col_subt);

        $col_descr->setTransformer(function ($value) {
            return Produto::findInTransaction('sample', $value)->descricao;
        });

        $col_subt->enableTotal('sum', 'R$', '.', '');

        $col_id->setVisibility(false);
        $col_uniq->setVisibility(false);


        // creates two datagrid actions
        $action1 = new TDataGridAction([$this, 'onEditItemProduto']);
        $action1->setFields(['uniqid', '*']);

        $action2 = new TDataGridAction([$this, 'onDeleteItem']);
        $action2->setField('uniqid');

        // add the actions to the datagrid
        $this->product_list->addAction($action1, _t('Edit'), 'far:edit blue');
        $this->product_list->addAction($action2, _t('Delete'), 'far:trash-alt red');

        $this->product_list->createModel();

        $panel = new TPanelGroup();
        $panel->add($this->product_list);
        $panel->getBody()->style = 'overflow-x:auto';
        $this->form->addContent([$panel]);

        $format_value = function ($value) {
            if (is_numeric($value)) {
                return 'R$ ' . number_format($value, 2, '.', '');
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
            $data->preco_unit      = '';
            // $data->product_detail_discount   = '';

            // send data, do not fire change/exit events
            TForm::sendData('form_entrada', $data, false, false);
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
        $data->preco_unit      = $param['preco_unit'];
        //$data->product_detail_discount   = $param['discount'];

        // send data, do not fire change/exit events
        TForm::sendData('form_entrada', $data, false, false);
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
        TForm::sendData('form_entrada', $data, false, false);

        // remove row
        TDataGrid::removeRowById('products_list', $param['uniqid']);
    }
    public function onEdit($param)
    {
        try {
            TTransaction::open('sample');

            if (isset($param['key'])) {
                $key = $param['key'];

                $object = new Entrada($key);
                $entrada_items = Item_Entrada::where('entrada_id', '=', $object->id)->load();
                $entradaItem = Item_Entrada::where('entrada_id', '=', $object->id)->first();
                $saidaItem = Item_Saida::where('produto_id', '=', $entradaItem->produto_id)->first();
                $this->form->getField('produto_id')->setEditable(false);
                $this->form->getField('quantidade')->setEditable(false);
                $this->form->getField('preco_unit')->setEditable(false);

                if ($object->status == 0) {
                    $alert = new TAlert('warning', 'Entrada foi cancelada.');
                    $alert->show();
                }else if(isset($saidaItem)){
                    $alert = new TAlert('warning', 'Existe Saida vinculada.');
                    $alert->show();
                }

                foreach ($entrada_items as $item) {
                    $item->uniqid = uniqid();
                    $row = $this->product_list->addItem($item);
                    $row->id = $item->uniqid;
                }
                $this->form->setData($object);
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

            // Antes de criar a entrada, verifique o fator de conversão
            foreach ($param['products_list_produto_id'] as $key => $item_id) {
                $produto = new Produto($item_id);

                $fatorConversao = Fator_Convesao::where('unidade_origem', '=', $produto->unidade_id)
                    ->where('unidade_destino', '=', $produto->unidade_saida)
                    ->first();

                if (!$fatorConversao) {
                    TTransaction::rollback(); // Anula a transação
                    throw new Exception('As unidades de medida não são compatíveis ou não há um fator de conversão definido.');
                }
            }
            $entrada = new Entrada;
            $entrada->fromArray((array) $data);

            if ($this->hasNegativeValues($param['products_list_quantidade']) || $this->hasNegativeValues($param['products_list_preco_unit'])) {
                throw new Exception('Não é permitido inserir valores negativos em quantidade ou preço unitário.');
            }
            if (!empty($entrada->id)) {
                new TMessage('warning', 'Esta entrada já foi salva.', $this->afterSaveAction);
            } else {
                $entrada->store();

                Item_Entrada::where('entrada_id', '=', $entrada->id)->delete();

                $total = 0;

                if (!empty($param['products_list_produto_id'])) {

                    foreach ($param['products_list_produto_id'] as $key => $item_id) {
                        $item = new Item_Entrada;
                        $item->produto_id  = $item_id;
                        $item->preco_unit  = (float) $param['products_list_preco_unit'][$key];
                        $item->quantidade      = (float) $param['products_list_quantidade'][$key];
                        $item->total       =  $item->preco_unit * $item->quantidade;


                        $preco_unit_estoque = $this->calcularValorUnit($item, $entrada);
                        $quantidade_estoque      = $this->calcularQuant($item, $entrada);

                        $item->entrada_id = $entrada->id;
                        $item->store();
                        $total += $item->total;
                        $this->insertEstoque($item, $item->total, $quantidade_estoque, $preco_unit_estoque);
                        $this->createMovement($item,$preco_unit_estoque,$quantidade_estoque);
                    }
                }

                $entrada->valor_total = $total;
                $entrada->created_at = date('Y-m-d H:i:s');
                $entrada->store();

                TForm::sendData('form_entrada', (object) ['id' => $entrada->id]);
                new TMessage('info', 'Registos Salvos',$this->afterSaveAction); //$this->afterSaveAction
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
            TTransaction::rollback();
        }
    }

    private function calcularValorUnit($item, $entrada_id)
    {
        try {
            $produto = new Produto($item->produto_id);

            $fatorConversao = Fator_Convesao::where('unidade_origem', '=', $produto->unidade_id)
                ->where('unidade_destino', '=', $produto->unidade_saida)
                ->first();

            if (!$fatorConversao) {
                $entrada = new Entrada($entrada_id);
                $entrada->delete();
                throw new Exception('As unidades de medida não são compatíveis ou não há um fator de conversão definido.');
            }

            $preco_unit = $item->preco_unit / $produto->qt_correspondente;
            return $preco_unit;
        } catch (Exception $e) {
            throw $e;
        }
    }
    private function calcularQuant($item, $entrada_id)
    {
        try {
            $produto = new Produto($item->produto_id);

            $fatorConversao = Fator_Convesao::where('unidade_origem', '=', $produto->unidade_id)
                ->where('unidade_destino', '=', $produto->unidade_saida)
                ->first();

            if (!$fatorConversao) {
                $entrada = new Entrada($entrada_id);
                $entrada->delete();
                throw new Exception('As unidades de medida não são compatíveis ou não há um fator de conversão definido.');
            }

            $quantidadeSaida = $item->quantidade * $produto->qt_correspondente;

            return $quantidadeSaida;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function insertEstoque($item, $total, $quantidade, $preco_unit)
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
                $mediaPonderadaEstoque = $preco_unit;
            }

            // Atualizar ou inserir o registro de estoque
            if ($estoque) {
                var_dump($estoque->quantidade);
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
                $estoque->valor_total = $item->quantidade * $item->preco_unit;
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

    private function createMovement($info, $preco_unit,$quantidade )
    {
        try {
            TTransaction::open('sample');
            //GRAVANDO MOVIMENTAÇÃO
            $mov = new Movimentacoes();
            $entrada = new Entrada($info->entrada_id);
            $usuario_logado = TSession::getValue('userid');
            $desc =  'Aquisição Cadastrada' ;
            $descricao = substr($desc, 0, 30) . '...';
            $mov->data_hora = date('Y-m-d H:i:s');
            $mov->descricao = $descricao;
            $mov->preco_unit = $preco_unit;
            $mov->produto_id = $info->produto_id;
            $mov->responsavel_id = $usuario_logado;
            $mov->quantidade = $quantidade ;

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
