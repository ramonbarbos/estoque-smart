<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn as DatagridTDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Wrapper\TDataGridColumn;
use Adianti\Widget\Wrapper\TDataGridAction;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Widget\Util\TMessage;
use Adianti\Widget\Util\TXMLBreadCrumb;

class EstoqueList extends TPage
{
    private $datagrid;

    use Adianti\base\AdiantiStandardListTrait;


    public function __construct()
    {
        parent::__construct();

        //Conexão com a tabela
        $this->setDatabase('sample');
        $this->setActiveRecord('Estoque');
        $this->setDefaultOrder('id', 'asc');
        $this->setLimit(10);


       
        // Create a datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        // Add columns to the datagrid
        $column_id = new DatagridTDataGridColumn('id', 'Codigo', 'left');
        $column_nf = new DatagridTDataGridColumn('nota_fiscal', 'Nota Fiscal', 'left');
        $column_produto = new DatagridTDataGridColumn('produto->nome', 'Produto', 'left');
        $column_produto_nome = new DatagridTDataGridColumn('produto->descricao', 'Descricao', 'left');
        $column_produto_nome = new DatagridTDataGridColumn('produto->unidadeDes->sigla', 'Unid.', 'left');
        $column_qtd = new DatagridTDataGridColumn('quantidade', 'Quantidade', 'left');
        $column_preco = new DatagridTDataGridColumn('preco_unit', 'Valor unidade', 'left');
        $column_total = new DatagridTDataGridColumn('valor_total', 'Total', 'left');

        $formato_valor = function ($value) {
            if (is_numeric($value)) {
                return 'R$ ' . number_format($value, 4, ',', '.');
            }
            return $value;
        };
        $column_preco->setTransformer($formato_valor);
        $column_total->setTransformer($formato_valor);
        

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_produto);
        $this->datagrid->addColumn($column_produto_nome);
        //$this->datagrid->addColumn($column_nf);
        $this->datagrid->addColumn($column_qtd);
        $this->datagrid->addColumn($column_preco);
        $this->datagrid->addColumn($column_total);

        $column_nf->setAction(new TAction([$this, 'onReload']), ['order' => 'nota_fiscal']);
        $column_produto->setAction(new TAction([$this, 'onReload']), ['order' => 'entrada_id']);
        $column_qtd->setAction(new TAction([$this, 'onReload']), ['order' => 'quantidade']);
        $column_preco->setAction(new TAction([$this, 'onReload']), ['order' => 'preco_unit']);
        $column_total->setAction(new TAction([$this, 'onReload']), ['order' => 'valor_total']);



        // Create the datagrid model
        $this->datagrid->createModel();

        //Criação de paginador
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        
     
        // Search box
        $input_search = new TEntry('input_search');
        $input_search->placeholder = _t('Search');
        $input_search->setSize('100%');

        // Enable search by column name
        $this->datagrid->enableSearch($input_search, 'produto->nome');


        // Panel to display the datagrid and search box
        $panel = new TPanelGroup('Mapa de Estoque');
        $panel->addHeaderWidget($input_search);
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter($this->pageNavigation);

        // Wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);

        parent::add($vbox);
    }

    /**
     * Load data into the datagrid
     */
   

    /**
     * Show the page
     */
    function show()
    {
        $this->onReload();
        parent::show();
    }
}
