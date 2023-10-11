<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class EntradaList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;

    use Adianti\base\AdiantiStandardListTrait;

    public function __construct()
    {

        parent::__construct();


        //Conexão com a tabela
        $this->setDatabase('sample');
        $this->setActiveRecord('Entrada');
        $this->setDefaultOrder('id', 'asc');
        $this->setLimit(10);



        $this->addFilterField('data_entrada', '=', 'data_entrada');
        $this->addFilterField('fornecedor_id', '=', 'fornecedor_id');
        $this->addFilterField('tp_entrada', '=', 'tp_entrada');

        //Criação do formulario 
        $this->form = new BootstrapFormBuilder('form_search_Entrada');
        $this->form->setFormTitle('Buscar no Estoque');

        //Criação de fields
        $data = new TDate('data_entrada');
        $fornecedor = new TDBUniqueSearch('fornecedor_id', 'sample', 'Fornecedor', 'id', 'nome');
        $fornecedor->setMinLength(0);
        $tp_entrada = new TDBUniqueSearch('tp_entrada', 'sample', 'Tipo_Entrada', 'id', 'nome');
        $tp_entrada->setMinLength(0);

        //Add filds na tela
        $this->form->addFields([new TLabel('Data')], [$data]);
        $this->form->addFields([new TLabel('Fornecedor')], [$fornecedor]);
        $this->form->addFields([new TLabel('Tipo')], [$tp_entrada]);

        //Tamanho dos fields
        $data->setSize('50%');

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        //Adicionar field de busca
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction(['EntradaForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');

        //Criando a data grid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        //Criando colunas da datagrid
        $column_id = new TDataGridColumn('id', 'Cod', 'left');
        $column_dt_entrada = new TDataGridColumn('data_entrada', 'Data', 'left');
        $column_fornc = new TDataGridColumn('fornecedor->nome', 'Fornecedor', 'left');
        $column_status = new TDataGridColumn('status', 'Status', 'left');
        $column_valor = new TDataGridColumn('valor_total', 'Total', 'left');
        $column_tipo = new TDataGridColumn('tipo->nome', 'Tipo', 'left');

        $column_dt_entrada->setTransformer(function ($value, $object, $row) {
            return date('d/m/Y', strtotime($value));
        });

        $formato_valor = function ($value) {
            if (is_numeric($value)) {
                return 'R$ ' . number_format($value, 2, ',', '.');
            }
            return $value;
        };
        $column_valor->setTransformer($formato_valor);

        $column_status->setTransformer(function ($value, $object, $row) {
            return ($value == 1) ? "<span style='color:green'>Ativo</span>" : "<span style='color:red'>Cancelado</span>";
        });

        //add coluna da datagrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_tipo);
        $this->datagrid->addColumn($column_dt_entrada);
        $this->datagrid->addColumn($column_fornc);
        $this->datagrid->addColumn($column_status);
        $this->datagrid->addColumn($column_valor);

        //Criando ações para o datagrid
        $column_dt_entrada->setAction(new TAction([$this, 'onReload']), ['order' => 'data_entrada']);
        $column_fornc->setAction(new TAction([$this, 'onReload']), ['order' => 'fornecedor_id']);
        $column_valor->setAction(new TAction([$this, 'onReload']), ['order' => 'valor_total']);
        $column_tipo->setAction(new TAction([$this, 'onReload']), ['order' => 'tp_entrada']);
        $column_status->setAction(new TAction([$this, 'onReload']), ['order' => 'status']);

        $action1 = new TDataGridAction(['EntradaForm', 'onEdit'], ['id' => '{id}', 'register_state' => 'false']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        $action3 = new TDataGridAction([$this, 'onCancel'], ['id' => '{id}']);

        //Adicionando a ação na tela
        $this->datagrid->addAction($action1, _t('Edit'), 'fa:edit blue');
        $this->datagrid->addAction($action2, _t('Delete'), 'fa:trash-alt red');
        $this->datagrid->addAction($action3, _t('Cancel'), 'fa:solid fa-ban black');


        //Criar datagrid 
        $this->datagrid->createModel();

        //Criação de paginador
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));



        //Enviar para tela
        $panel = new TPanelGroup('', 'white');
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);

        //Exportar
        $drodown = new TDropDown('Exportar', 'fa:list');
        $drodown->setPullSide('right');
        $drodown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $drodown->addAction('Salvar como CSV', new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static' => '1']), 'fa:table green');
        $drodown->addAction('Salvar como PDF', new TAction([$this, 'onExportPDF'], ['register_state' => 'false',  'static' => '1']), 'fa:file-pdf red');
        $panel->addHeaderWidget($drodown);

        //Vertical container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);

        parent::add($container);
    }
    public function onCancel($param)
    {
        try {
            if (isset($param['id'])) {
                $id = $param['id'];

                TTransaction::open('sample');

                $entrada = new Entrada($id);
                if ($entrada) {

                    $itemEntrada = Item_Entrada::where('entrada_id', '=', $entrada->id)->first();
                    $saida = Item_Saida::where('produto_id', '=', $itemEntrada->produto_id)->load();

                    if (isset($saida)) {
                        throw new Exception("Não foi possivel cancelar, verifique saidas.");
                    } else {
                        if ($entrada->status == 1) {
                            $entrada->status = 0;

                            $this->cancelEstoque($entrada);
                            $entrada->store();

                            TTransaction::close();

                            new TMessage('info', 'Entrada Cancelada.', $this->afterSaveAction);
                            $this->onReload([]);
                        } else {
                            throw new Exception("A entrada já está cancelada.");
                        }
                    }
                } else {
                    throw new Exception("Entrada não encontrada.");
                }
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    private function cancelEstoque($entrada)
    {
        try {
            TTransaction::open('sample');

            $itens = Item_Entrada::where('entrada_id', '=', $entrada->id)->load();


            foreach ($itens as $item) {
                $quantidade_estoque      = $this->calcularQuant($item, $entrada);
                $produto_id = $item->produto_id;
                $quantidade = $quantidade_estoque;
                $totalValor = $item->total;

                $estoque = Estoque::where('produto_id', '=', $produto_id)->first();

                if ($estoque) {
                    if ($estoque->quantidade - $quantidade < 0) {
                        throw new Exception("A quantidade no estoque do produto $produto_id não pode ser negativa.");
                    }

                    if ($estoque->valor_total - $totalValor < 0) {
                        throw new Exception("O valor total no estoque do produto $produto_id não pode ser negativo.");
                    }

                    $estoque->quantidade -= $quantidade;
                    $estoque->valor_total -= $totalValor;

                    if ($estoque->quantidade == 0) {
                        $estoque->preco_unit = 0;
                    } else {
                        $estoque->preco_unit = $estoque->valor_total / $estoque->quantidade;
                    }
                    $estoque->store();
                    $this->cancelMovement($item);
                }
            }
            TTransaction::close();
        } catch (Exception $e) {
            // Tratar erros aqui, se necessário
            TTransaction::rollback();
            throw new Exception("Erro ao atualizar o estoque: " . $e->getMessage());
        }
    }

    private function calcularQuant($item, $entrada_id)
    {
        $produto = new Produto($item->produto_id);

        $fatorConversao = Fator_Convesao::where('unidade_origem', '=', $produto->unidade_id)
            ->where('unidade_destino', '=', $produto->unidade_saida)
            ->first();

        if (!$fatorConversao) {
            $entrada = new Entrada($entrada_id);
            $entrada->delete();
            throw new Exception('As unidades de medida não são compatíveis ou não há um fator de conversão definido.');
        }

        // Ajusta a quantidade para a unidade de saída usando o fator de conversão
        $quantidadeSaida = $item->quantidade * $produto->qt_correspondente;

        return $quantidadeSaida;
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
    public function onDelete($param)
    {
        try {
            if (isset($param['key'])) {

                $id = $param['key'];

                TTransaction::open('sample');
                $entrada = new Entrada($id);

                if ($entrada->status == 0) {
                    if ($entrada) {
                        // Verifique se esta é a última entrada relacionada ao estoque

                        $itemEntrada = Item_Entrada::where('entrada_id', '=', $entrada->id)->first();
                        $estoque = Estoque::where('produto_id', '=', $itemEntrada->produto_id)->first();
                        $this->deleteMovement($entrada);
                        if ($itemEntrada && $estoque) {

                            $outrasEntradas = Item_Entrada::where('produto_id', '=', $itemEntrada->produto_id)->count();
    
                                if ($outrasEntradas == 1) {
                                    // Esta é a última entrada, então exclua o estoque
                                    $estoque = new Estoque($estoque->id);
                                    $estoque->delete();
                                }
                            }
                        Item_Entrada::where('entrada_id', '=', $entrada->id)->delete();
                        $entrada->delete();

                        new TMessage('info', 'Entrada deletada.', $this->afterSaveAction);
                    }

                    $this->onReload([]);
                } else {
                    new TMessage('warning', 'É necessario a entrada está cancelada.', $this->afterSaveAction);
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage(), $this->afterSaveAction);
            TTransaction::rollback();
        }
    }

    private function deleteMovement($entrada)
    {
        //GRAVANDO MOVIMENTAÇÃO



        $mov = new Movimentacoes();
        $usuario_logado = TSession::getValue('userid');
        $descricao = 'Entrada Deletada';

        $item = Item_Entrada::where('entrada_id', '=', $entrada->id)->first();
        @$estoque = Estoque::where('produto_id', '=', $item->produto_id)->first();

        $quantidade_estoque      = $this->calcularQuant($item, $item->entrada_id);

        $mov->data_hora = date('Y-m-d H:i:s');
        $mov->descricao = $descricao;
        @$mov->produto_id = $estoque->produto_id;
        $mov->responsavel_id = $usuario_logado;
        $mov->saldo_anterior = $estoque->valor_total ?? 0;
        $mov->quantidade = $quantidade_estoque ?? 0;
        $mov->valor_total = $item->valor_total ?? 0;

        $mov->store();
    }

    private function cancelMovement($info)
    {
        try {
            TTransaction::open('sample');
            //GRAVANDO MOVIMENTAÇÃO
            $mov = new Movimentacoes();
            $entrada = new Entrada($info->entrada_id);
            $estoque = Estoque::where('produto_id', '=', $entrada->produto_id)->first();

            $preco_unit_estoque = $this->calcularValorUnit($info, $entrada);
            $quantidade_estoque      = $this->calcularQuant($info, $entrada);

            $usuario_logado = TSession::getValue('userid');
            $desc =  'Entrada Cancelada.';
            $mov->data_hora = date('Y-m-d H:i:s');
            $mov->descricao = $desc;
            $mov->preco_unit = $preco_unit_estoque;
            $mov->produto_id = $info->produto_id;
            $mov->responsavel_id = $usuario_logado;
            $mov->saldo_anterior = $estoque->valor_total ?? 0;
            $mov->quantidade = $quantidade_estoque ?? 0;
            $mov->valor_total = $info->valor_total ?? 0;


            $mov->store();
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function hasRelatedOutbound($id)
    {
        try {
            // Verifique se há saídas relacionadas a este estoque
            TTransaction::open('sample');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('estoque_id', '=', $id));
            $repository = new TRepository('Saida');
            $count = $repository->count($criteria);
            TTransaction::close();

            // Se houver saídas relacionadas, retorne true
            return $count > 0;
        } catch (Exception $e) {
            // Em caso de erro, trate-o de acordo com suas necessidades
            new TMessage('error', $e->getMessage());
            return false;
        }
    }
}
