<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
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

class RetornoClienteList extends TPage
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
    $this->setActiveRecord('Retorno_Cliente');
    $this->setDefaultOrder('id', 'asc');
    $this->setLimit(10);




    $this->addFilterField('data_retorno', '=', 'data_retorno');
    $this->addFilterField('produto_id', '=', 'produto_id');
    $this->addFilterField('cliente_id', '=', 'cliente_id');

    //Criação do formulario 
    $this->form = new BootstrapFormBuilder('form_search_Retorno_Cliente');
    $this->form->setFormTitle('Devolução do Cliente');

          //Criação de fields
          $data = new TDate('data_retorno');
          $produto = new TDBUniqueSearch('produto_id', 'sample', 'Produto', 'id', 'nome');
          $produto->setMinLength(0);
          $cliente = new TDBUniqueSearch('cliente_id', 'sample', 'Cliente', 'id', 'nome');
          $cliente->setMinLength(0);
  
          //Add filds na tela
          $this->form->addFields([new TLabel('Data')], [$data]);
          $this->form->addFields([new TLabel('Produto')], [$produto]);
          $this->form->addFields([new TLabel('Cliente')], [$cliente]);

   //Tamanho dos fields
   $data->setSize('50%');


    $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

    //Adicionar field de busca
    $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
    $btn->class = 'btn btn-sm btn-primary';
    $this->form->addActionLink(_t('New'), new TAction(['RetornoClienteForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');

    //Criando a data grid
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->style = 'width: 100%';

    //Criando colunas da datagrid
    $column_id = new TDataGridColumn('id', 'Cod', 'center');
    $column_nf = new TDataGridColumn('saida->nota_fiscal', 'Nota Fiscal', 'center');
    $column_produto = new TDataGridColumn('produto->nome', 'Produto', 'center');
    $column_dt_retorno = new TDataGridColumn('data_retorno', 'Data de Retorno', 'center');
    $column_cli = new TDataGridColumn('cliente->nome', 'Cliente', 'center');
    $column_qtd = new TDataGridColumn('quantidade', 'Quantidade', 'center');
    $column_preco = new TDataGridColumn('preco_unit', 'Valor Unid.', 'center');

    //$desc->setProperty('style','background:pink');
    $column_preco->setDataProperty('style', 'font-weight: bold');
    $column_preco->setTransformer(array($this, 'formatSalary'));

    $column_dt_retorno->setTransformer(function ($value, $object, $row) {
      return date('d/m/Y', strtotime($value));
    });
    $column_preco->setTransformer(function ($value, $object, $row) {
      return 'R$ ' . number_format($value, 2, ',', '.');
    });

    //add coluna da datagrid
    $this->datagrid->addColumn($column_id);
    $this->datagrid->addColumn($column_produto);
    $this->datagrid->addColumn($column_nf);
    $this->datagrid->addColumn($column_dt_retorno);
    $this->datagrid->addColumn($column_cli);
    $this->datagrid->addColumn($column_qtd);
    $this->datagrid->addColumn($column_preco);

    //Criando ações para o datagrid
    $column_produto->setAction(new TAction([$this, 'onReload']), ['order' => 'produto_id']);
    $column_nf->setAction(new TAction([$this, 'onReload']), ['order' => 'nota_fiscal']);
    $column_dt_retorno->setAction(new TAction([$this, 'onReload']), ['order' => 'data_retorno']);
    $column_cli->setAction(new TAction([$this, 'onReload']), ['order' => 'cliente_id']);
    $column_qtd->setAction(new TAction([$this, 'onReload']), ['order' => 'quantidade']);
    $column_preco->setAction(new TAction([$this, 'onReload']), ['order' => 'preco_unit']);
    $column_preco->setAction(new TAction([$this, 'onReload']), ['order' => 'valor_total']);

    $action1 = new TDataGridAction(['RetornoClienteForm', 'onEdit'], ['id' => '{id}', 'register_state' => 'false']);
    $action2 = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
    $action3 = new TDataGridAction([$this, 'onCancel'], ['id' => '{id}']);

    //Adicionando a ação na tela
    $this->datagrid->addAction($action1, _t('Edit'), 'fa:edit blue');
    //$this->datagrid->addAction($action2, _t('Delete'), 'fa:trash-alt red');
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

  public function formatSalary($stock, $object, $row)
  {
    $number = number_format($stock, 2, ',', '.');
    if ($stock > 0) {
      return "<span style='color:blue'>$number</span>";
    } else {
      $row->style = "background: #FFF9A7";
      return "<span style='color:red'>$number</span>";
    }
  }

  public function onDelete($param)
  {
    if (isset($param['key'])) {
      // Obtém o ID do estoque a ser excluído

      new TMessage('error', 'Não é possível excluir, pois o valor foi devolvido ao estoque.');
    }
  }
  public function onCancel($param)
  {
    if (isset($param['key'])) {
      // Obtém o ID do estoque a ser excluído


      $id = $param['key'];

      // Abre uma transação
      TTransaction::open('sample');

      // Obtém a devolução do cliente pelo ID
      $devolucao = new Retorno_Cliente($id);
      if ($devolucao) {


        // Verifique se já existe uma entrada no mapa de estoque para esse produto
        $estoque = Estoque::where('id', '=', $devolucao->estoque_id)->load();
        $estoque = $estoque[0];
        $novaQuantidade = $estoque->quantidade - $devolucao->quantidade;
        $valor_atual = $estoque->valor_total - $devolucao->valor_total;
        $estoque->valor_total = $valor_atual;
        $estoque->quantidade = $novaQuantidade;
        $estoque->store();



        try {
          // Exclua o estoque
          TTransaction::open('sample');
          $object = new Retorno_Cliente($id);
          

          $saida = Saida::where('id', '=', $object->saida_id)
            ->first();
          $saida->quant_retirada = $saida->quant_retirada+ $object->quantidade;
          $saida->store();
          $object->delete();
          TTransaction::close();

          // Recarregue a listagem
          $this->onReload();
          new TMessage('info', 'Registro cancelado com sucesso.');
        } catch (Exception $e) {
          new TMessage('error', $e->getMessage());
        }


        $this->onReload();
      }

      TTransaction::close();
    }
  }
}
