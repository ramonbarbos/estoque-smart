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
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class EntradaList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $formgrid;
    private $deleteButton;

    use Adianti\base\AdiantiStandardListTrait;

    public function __construct()
    {

        parent::__construct();


        //Conexão com a tabela
        $this->setDatabase('sample');
        $this->setActiveRecord('Entrada');
        $this->setDefaultOrder('id', 'asc');
        $this->setLimit(10);



        $this->addFilterField('nota_fiscal', 'like', 'nota_fiscal');

        //Criação do formulario 
        $this->form = new BootstrapFormBuilder('formulario Entrada');
        $this->form->setFormTitle('Buscar no Estoque');

        //Criação de fields
        $nf = new TEntry('Nota Fiscal');

        //Add filds na tela
        $this->form->addFields([new TLabel('Nota Fiscal')], [$nf]);

        //Tamanho dos fields
        $nf->setSize('100%');

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        //Adicionar field de busca
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction(['EntradaForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');

        //Criando a data grid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        //Criando colunas da datagrid
        $column_id = new TDataGridColumn('id', 'Cod', 'center', '5%');
        $column_nf = new TDataGridColumn('nota_fiscal', 'Nota Fiscal', 'center', '10%');
        $column_produto = new TDataGridColumn('produto->nome', 'Produto', 'center', '10%');
        $column_dt_entrada = new TDataGridColumn('data_entrada', 'Data', 'center', '10%');
        $column_fornc = new TDataGridColumn('fornecedor->nome', 'Fornecedor', 'center', '10%');
        $column_qtd = new TDataGridColumn('quantidade', 'Quantidade', 'center', '10%');
        $column_valor = new TDataGridColumn('valor_total', 'Total', 'center', '10%');
        $column_preco = new TDataGridColumn('preco_unit', 'Unidade', 'center', '10%');
        $column_tipo = new TDataGridColumn('tipo->nome', 'Tipo', 'center', '10%');

        $column_dt_entrada->setTransformer(function ($value, $object, $row) {
            return date('d/m/Y', strtotime($value));
        });
        $column_preco->setTransformer(function ($value, $object, $row) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        });

        //add coluna da datagrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_produto);
        $this->datagrid->addColumn($column_tipo);
        $this->datagrid->addColumn($column_dt_entrada);
        $this->datagrid->addColumn($column_nf);
        $this->datagrid->addColumn($column_fornc);
        $this->datagrid->addColumn($column_qtd);
        // $this->datagrid->addColumn($column_preco);
        $this->datagrid->addColumn($column_valor);

        //Criando ações para o datagrid
        $column_produto->setAction(new TAction([$this, 'onReload']), ['order' => 'produto_id']);
        $column_nf->setAction(new TAction([$this, 'onReload']), ['order' => 'nota_fiscal']);
        $column_dt_entrada->setAction(new TAction([$this, 'onReload']), ['order' => 'data_entrada']);
        $column_fornc->setAction(new TAction([$this, 'onReload']), ['order' => 'fornecedor_id']);
        $column_qtd->setAction(new TAction([$this, 'onReload']), ['order' => 'quantidade']);
        // $column_preco->setAction(new TAction([$this, 'onReload']), ['order' => 'preco_unit']);
        $column_valor->setAction(new TAction([$this, 'onReload']), ['order' => 'valor_total']);
        $column_tipo->setAction(new TAction([$this, 'onReload']), ['order' => 'tp_entrada']);

        $action1 = new TDataGridAction(['EntradaForm', 'onEdit'], ['id' => '{id}', 'register_state' => 'false']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);

        //Adicionando a ação na tela
        $this->datagrid->addAction($action1, _t('Edit'), 'fa:edit blue');
        $this->datagrid->addAction($action2, _t('Delete'), 'fa:trash-alt red');


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
    public function onDelete($param)
    {
        try {
            if (isset($param['key'])) {
                // Obtém o ID do estoque a ser excluído

                $id = $param['key'];



                TTransaction::open('sample');
                $estoque = Estoque::where('entrada_id', '=', $id)
                    ->first();

                if ($estoque) {

                    $estoque_id = $estoque->id;
                    // Verifica se existem saídas relacionadas a este estoque
                    if ($this->hasRelatedOutbound($estoque_id)) {
                        new TMessage('error', 'Não é possível excluir este estoque, pois existem vinculações.');
                    } else {
                        try {
                            // Exclua o estoque
                            TTransaction::open('sample');
                            $object = new Entrada($id);
                            $object->delete();
                            $object = new Estoque($estoque_id);
                            $object->delete();
                            TTransaction::close();

                            // Recarregue a listagem
                            $this->onReload();
                            new TMessage('info', 'Registro excluído com sucesso.', $this->afterSaveAction);
                        } catch (Exception $e) {
                            new TMessage('error', $e->getMessage());
                        }
                    }
                } else {
                    try {
                        TTransaction::open('sample');
                        $object = new Entrada($id);
                        $object->delete();
                        TTransaction::close();
                        // Recarregue a listagem
                        $this->onReload();
                        new TMessage('info', 'Registro excluído com sucesso.', $this->afterSaveAction);
                    } catch (Exception $e) {
                        new TMessage('error', $e->getMessage());
                    }
                }
            }
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
