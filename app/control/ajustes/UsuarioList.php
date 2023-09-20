<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class UsuarioList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $formgrid;
    private $deleteButton;

    use Adianti\base\AdiantiStandardListTrait;
    
    public function __construct(){

        parent::__construct();


        //Conexão com a tabela
        $this->setDatabase('sample');
        $this->setActiveRecord('Usuario');
        $this->setDefaultOrder('id', 'asc');
        $this->setLimit(10);

        $this->addFilterField('nome', 'like', 'nome');
        $this->addFilterField('login', 'like', 'login');
        $this->addFilterField('ativo', '=', 'ativo');
        $this->addFilterField('cargo', '=', 'cargo');

        //Criação do formulario 
        $this->form = new BootstrapFormBuilder('formulario usuario');
        $this->form->setFormTitle('Usuario');

        //Criação de fields
        $nome = new TEntry('nome');
        $login = new TEntry('login');
        $ativo = new TCombo('ativo');
        $cargo = new TCombo('cargo');

        $ativo->addItems( ['1' => 'Ativo', '0' => 'Inativo'] );
        $cargo->addItems( ['0' => 'Normal', '1' => 'Sub-Administrador','2' => 'Administrador'] );

        //Add filds na tela
        $this->form->addFields( [new TLabel('Nome')], [ $nome ], [new TLabel('Login')], [ $login ]  );
        $this->form->addFields( [new TLabel('Ativo')], [ $ativo ], [new TLabel('Cargo')], [ $cargo ] );

        //Tamanho dos fields
        $nome->setSize('100%');
        $login->setSize('100%');
        $ativo->setSize('100%');
        $cargo->setSize('100%');

        $this->form->setData( TSession::getValue( __CLASS__.'_filter_data') );

        //Adicionar field de busca
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction(['UsuarioForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green'  );

        //Criando a data grid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        //Criando colunas da datagrid
        $column_id = new TDataGridColumn('id', 'Cod.', 'center', '10%');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_login = new TDataGridColumn('login', 'login', 'left');
        $column_senha = new TDataGridColumn('senha', 'senha', 'left');
        $column_ativo = new TDataGridColumn('ativo', 'ativo', 'left');
        $column_cargo = new TDataGridColumn('cargo', 'cargo', 'left');

        //add coluna da datagrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_login);
        $this->datagrid->addColumn($column_senha);
        $this->datagrid->addColumn($column_ativo);
        $this->datagrid->addColumn($column_cargo);

        //Criando ações para o datagrid
        $column_id->setAction(new TAction([$this, 'onReload']), ['order'=> 'id']);
        $column_nome->setAction(new TAction([$this, 'onReload']), ['order'=> 'nome']);
        $column_login->setAction(new TAction([$this, 'onReload']), ['order'=> 'login']);
        $column_senha->setAction(new TAction([$this, 'onReload']), ['order'=> 'senha']);
        $column_ativo->setAction(new TAction([$this, 'onReload']), ['order'=> 'ativo']);
        $column_cargo->setAction(new TAction([$this, 'onReload']), ['order'=> 'cargo']);

        $action1 = new TDataGridAction(['UsuarioForm', 'onEdit'], ['id'=> '{id}', 'register_state' => 'false']);
        $action2 = new TDataGridAction([ $this, 'onDelete'], ['id'=> '{id}']);

        //Adicionando a ação na tela
        $this->datagrid->addAction($action1, _t('Edit'), 'fa:edit blue' );
        $this->datagrid->addAction($action2, _t('Delete'), 'fa:trash-alt red' );


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
          $drodown->addAction('Salvar como CSV', new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static'=>'1']), 'fa:table green');
          $drodown->addAction('Salvar como PDF', new TAction([$this, 'onExportPDF'], ['register_state' => 'false',  'static'=>'1']), 'fa:file-pdf red');
          $panel->addHeaderWidget( $drodown);

        //Vertical container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
    
        parent::add($container);

       

    }
  
}