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
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class SaidaList extends TPage
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
    $this->setActiveRecord('Saida');
    $this->setDefaultOrder('id', 'asc');
    $this->setLimit(10);

    $this->addFilterField('nota_fiscal', 'like', 'nota_fiscal');

    //Criação do formulario 
    $this->form = new BootstrapFormBuilder('formulario saida');
    $this->form->setFormTitle('Saida do Estoque');

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
    $this->form->addActionLink(_t('New'), new TAction(['SaidaForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');

    //Criando a data grid
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->style = 'width: 100%';

    //Criando colunas da datagrid
    $column_id = new TDataGridColumn('id', 'Cod', 'center');
    $column_nf = new TDataGridColumn('nota_fiscal', 'Nota Fiscal', 'center');
    $column_produto = new TDataGridColumn('produto->nome', 'Produto', 'center');
    $column_dt_saida = new TDataGridColumn('data_saida', 'Data de Saida', 'center');
    $column_clie = new TDataGridColumn('cliente->nome', 'Cliente', 'center');
    $column_qtd = new TDataGridColumn('quantidade', 'Quant.', 'center');
    $column_preco = new TDataGridColumn('preco_unit', 'Valor Unid.', 'center');
    $column_total = new TDataGridColumn('valor_total', 'Total', 'center');

    $column_dt_saida->setTransformer(function ($value, $object, $row) {
      // Formate a data para o formato desejado (por exemplo, 'd/m/Y')
      return date('d/m/Y', strtotime($value));
    });
    $column_preco->setTransformer(function ($value, $object, $row) {
      return 'R$ ' . number_format($value, 2, ',', '.');
    });
    $column_total->setTransformer(function ($value, $object, $row) {
      return 'R$ ' . number_format($value, 2, ',', '.');
    });
    //add coluna da datagrid
    $this->datagrid->addColumn($column_id);
    $this->datagrid->addColumn($column_produto);
    $this->datagrid->addColumn($column_nf);
    $this->datagrid->addColumn($column_dt_saida);
    $this->datagrid->addColumn($column_clie);
    $this->datagrid->addColumn($column_qtd);
    $this->datagrid->addColumn($column_preco);

    //Criando ações para o datagrid
    $column_produto->setAction(new TAction([$this, 'onReload']), ['order' => 'produto_id']);
    $column_nf->setAction(new TAction([$this, 'onReload']), ['order' => 'nota_fiscal']);
    $column_dt_saida->setAction(new TAction([$this, 'onReload']), ['order' => 'data_saida']);
    $column_clie->setAction(new TAction([$this, 'onReload']), ['order' => 'cliente_id']);
    $column_qtd->setAction(new TAction([$this, 'onReload']), ['order' => 'quantidade']);
    $column_preco->setAction(new TAction([$this, 'onReload']), ['order' => 'preco_unit']);
    $column_total->setAction(new TAction([$this, 'onReload']), ['order' => 'valor_total']);

    $action1 = new TDataGridAction(['SaidaForm', 'onEdit'], ['id' => '{id}', 'register_state' => 'false']);
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
    if (isset($param['key'])) {
      // Obtém o ID do estoque a ser excluído
      $id = $param['key']; //ID da saida

      TTransaction::open('sample');
      $retorno = Retorno_Cliente::where('saida_id', '=', $id)
        ->first();
      if ($retorno) {
        $retorno_id =  $retorno->id;

        // Verifica se existem saídas relacionadas a este estoque
        if ($this->hasRelatedOutbound($retorno_id)) {
          new TMessage('error', 'Não é possível excluir esta saida, pois existem vinculações.');
        } else {
          try {
            // Exclua o estoque
            TTransaction::open('sample');
            $object = new Saida($id);
            $object->delete();

            TTransaction::close();

            // Recarregue a listagem
            $this->onReload();
            new TMessage('info', 'Registro excluído com sucesso.');
          } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
          }
        }
      } else {
        // Exclua o estoque
        TTransaction::open('sample');
        $object = new Saida($id);
        $object->delete();

        TTransaction::close();

        // Recarregue a listagem
        $this->onReload();
      }
    }
  }

  private function hasRelatedOutbound($id)
  {
    try {
      // Verifique se há saídas relacionadas a este estoque
      TTransaction::open('sample');
      $criteria = new TCriteria;
      $criteria->add(new TFilter('id', '=', $id));
      $repository = new TRepository('Retorno_Cliente');
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
