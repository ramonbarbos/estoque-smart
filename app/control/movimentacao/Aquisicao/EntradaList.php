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
        $this->addFilterField('produto_id', '=', 'produto_id');
        $this->addFilterField('fornecedor_id', '=', 'fornecedor_id');

        //Criação do formulario 
        $this->form = new BootstrapFormBuilder('form_search_Entrada');
        $this->form->setFormTitle('Buscar no Estoque');

        //Criação de fields
        $data = new TDate('data_entrada');
        $produto = new TDBUniqueSearch('produto_id', 'sample', 'Produto', 'id', 'nome');
        $produto->setMinLength(0);
        $fornecedor = new TDBUniqueSearch('fornecedor_id', 'sample', 'Fornecedor', 'id', 'nome');
        $fornecedor->setMinLength(0);

        //Add filds na tela
        $this->form->addFields([new TLabel('Data')], [$data]);
        $this->form->addFields([new TLabel('Produto')], [$produto]);
        $this->form->addFields([new TLabel('Fornecedor')], [$fornecedor]);

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
        // $column_preco->setTransformer(function ($value, $object, $row) {
        //     return 'R$ ' . number_format($value, 2, ',', '.');
        // });
        $column_status->setTransformer(function ($value, $object, $row) {
            return ($value == 1) ? "<span style='color:green'>Ativo</span>" : "<span style='color:red'>Cancelado</span>";
        });

        //add coluna da datagrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_tipo);
        $this->datagrid->addColumn($column_dt_entrada);
        $this->datagrid->addColumn($column_fornc);
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
                $produto_id = $item->produto_id;
                $quantidade = $item->quantidade;
                $totalValor = $item->total;
    
                $estoque = Estoque::where('produto_id', '=', $produto_id)->first();
    
                if ($estoque) {
                    // Verifique se a quantidade após a subtração será negativa
                    if ($estoque->quantidade - $quantidade < 0) {
                        throw new Exception("A quantidade no estoque do produto $produto_id não pode ser negativa.");
                    }
    
                    // Verifique se o valor total após a subtração será negativo
                    if ($estoque->valor_total - $totalValor < 0) {
                        throw new Exception("O valor total no estoque do produto $produto_id não pode ser negativo.");
                    }
    
                    // Subtraia a quantidade e o valor total do estoque do produto
                    $estoque->quantidade -= $quantidade;
                    $estoque->valor_total -= $totalValor;
    
                    // Se a quantidade no estoque for zero, defina o preço unitário como zero para evitar divisão por zero
                    if ($estoque->quantidade == 0) {
                        $estoque->preco_unit = 0;
                    } else {
                        // Calcule o novo preço unitário com base no valor total e na quantidade restante
                        $estoque->preco_unit = $estoque->valor_total / $estoque->quantidade;
                    }
    
                    $estoque->store();
                }
            }
            TTransaction::close();
        } catch (Exception $e) {
            // Tratar erros aqui, se necessário
            TTransaction::rollback();
            throw new Exception("Erro ao atualizar o estoque: " . $e->getMessage());
        }
    }
    public function onDelete($param)
    {
        try {
            if (isset($param['key'])) {
                // Obtém o ID do estoque a ser excluído

                $id = $param['key'];

                TTransaction::open('sample');
                $entrada = new Entrada($id);

                if ($entrada->status == 0) {
                    if ($entrada) {
                        Item_Entrada::where('entrada_id', '=', $entrada->id)->delete();
                        $entrada->delete();
                        new TMessage('info', 'Entrada deletada.', $this->afterSaveAction);
                    }
                } else {
                    new TMessage('warning', 'É necessario a entrada está cancelada.', $this->afterSaveAction);
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function createDeleteMovement($entrada)
    {
        //GRAVANDO MOVIMENTAÇÃO
        $mov = new Movimentacoes();
        $usuario_logado = TSession::getValue('userid');
        $descricao = 'Exclusão de Entrada';


        $estoque = Estoque::where('produto_id', '=', $entrada->produto_id)->first();

        $mov->data_hora = date('Y-m-d H:i:s');
        $mov->descricao = $descricao;
        $mov->produto_id = $entrada->produto_id;
        $mov->responsavel_id = $usuario_logado;
        $mov->saldoEstoque = $estoque->valor_total ?? 0;
        $mov->quantidade = $entrada->quantidade ?? 0;
        $mov->valor_total = $entrada->valor_total ?? 0;

        $mov->store();
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