<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
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
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class ProdutoList extends TPage
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
    $this->setActiveRecord('Produto');
    $this->setDefaultOrder('id', 'asc');
    $this->setLimit(10);

    $this->addFilterField('nome', 'like', 'nome');

    //Criação do formulario 
    $this->form = new BootstrapFormBuilder('form_search_Produto');
    $this->form->setFormTitle('Produto');

    //Criação de fields
    $nome = new TEntry('nome');

    //Add filds na tela
    $this->form->addFields([new TLabel('Nome do Produto')], [$nome]);

    //Tamanho dos fields
    $nome->setSize('100%');

    $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

    //Adicionar field de busca
    $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
    $btn->class = 'btn btn-sm btn-primary';
    $this->form->addActionLink(_t('New'), new TAction(['ProdutoForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');

    //Criando a data grid
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->style = 'width: 100%';

    //Criando colunas da datagrid
    $column_id = new TDataGridColumn('id', 'Codigo', 'left',);
    $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
    $column_cadastro = new TDataGridColumn('created_at', 'Cadastro', 'left');
    $column_uni = new TDataGridColumn('unidade->sigla', 'UND', 'left');

    $column_cadastro->setTransformer(function ($value, $object, $row) {
      return date('d/m/Y', strtotime($value));
    });

    //add coluna da datagrid
    $this->datagrid->addColumn($column_id);
    $this->datagrid->addColumn($column_nome);
    $this->datagrid->addColumn($column_cadastro);
    $this->datagrid->addColumn($column_uni);

    //Criando ações para o datagrid
    $column_id->setAction(new TAction([$this, 'onReload']), ['order' => 'id']);
    $column_nome->setAction(new TAction([$this, 'onReload']), ['order' => 'nome']);
    $column_nome->setAction(new TAction([$this, 'onReload']), ['order' => 'descricao']);
    $column_nome->setAction(new TAction([$this, 'onReload']), ['order' => 'unidade_id']);


    $action1 = new TDataGridAction(['ProdutoForm', 'onEdit'], ['id' => '{id}', 'register_state' => 'false']);
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
    $container->add($this->form);
    $container->add($panel);

    parent::add($container);
  }
  public function onDelete($param)
  {
    if (isset($param['key'])) {
      // Obtém o ID do cliente a ser excluído
      $id = $param['key'];

      TTransaction::open('sample');
      $itemEntrada = Item_Entrada::where('produto_id', '=', $id)->first();

      if ($itemEntrada) {
        $retorno_id =  $itemEntrada->id;

        if ($this->hasRelatedOutbound($retorno_id)) {
          new TMessage('error', 'Não é possível excluir este Produto, pois existem vinculações.');
        } else {
          $object = new Produto($id);
          $object->delete();


          $this->onReload();
          new TMessage('info', 'Registro excluído com sucesso.');
        }
      } else {
        $object = new Produto($id);
        $object->delete();
        $this->onReload();
        new TMessage('info', 'Registro excluído com sucesso.');

        TTransaction::close();
      }
    }
  }


  private function hasRelatedOutbound($id)
  {
    try {
      // Verifique se há entrada relacionadas a este estoque
      TTransaction::open('sample');
      $criteria = new TCriteria;
      $criteria->add(new TFilter('id', '=', $id));
      $repository = new TRepository('Entrada');
      $count = $repository->count($criteria);
      TTransaction::close();

      return $count > 0;
    } catch (Exception $e) {
      // Em caso de erro, trate-o de acordo com suas necessidades
      new TMessage('error', $e->getMessage());
      return false;
    }
  }
}
