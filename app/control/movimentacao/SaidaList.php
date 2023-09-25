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
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class SaidaList extends TPage
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
    $this->setActiveRecord('Saida');
    $this->setDefaultOrder('id', 'asc');
    $this->setLimit(10);

    $this->addFilterField('data_saida', '=', 'data_saida');
    $this->addFilterField('produto_id', '=', 'produto_id');
    $this->addFilterField('fornecedor_id', '=', 'fornecedor_id');


    //Criação do formulario 
    $this->form = new BootstrapFormBuilder('form_search_Saida');
    $this->form->setFormTitle('Saida do Estoque');

    //Criação de fields
    $data = new TDate('data_saida');
    $produto = new TDBUniqueSearch('produto_id', 'sample', 'Produto', 'id', 'nome');
    $produto->setMinLength(0);
    $fornecedor = new TDBUniqueSearch('fornecedor_id', 'sample', 'Fornecedor', 'id', 'nome');
    $fornecedor->setMinLength(0);

    //Add filds na tela
    $this->form->addFields([new TLabel('Data')], [$data]);
    $this->form->addFields([new TLabel('Produto')], [$produto]);
    $this->form->addFields([new TLabel('Nota Fiscal')], [$fornecedor]);

    //Tamanho dos fields
    $data->setSize('50%');
    $produto->setSize('100%');
    $fornecedor->setSize('100%');

    $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

    //Adicionar field de busca
    $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
    $btn->class = 'btn btn-sm btn-primary';
    $this->form->addActionLink(_t('New'), new TAction(['SaidaForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');

    //Criando a data grid
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->style = 'width: 100%';

    //Criando colunas da datagrid
    $column_id = new TDataGridColumn('id', 'Codigo', 'left');
    $column_nf = new TDataGridColumn('nota_fiscal', 'Nota Fiscal', 'left');
    $column_produto = new TDataGridColumn('produto->nome', 'Produto', 'left');
    $column_dt_saida = new TDataGridColumn('data_saida', 'Data de Saida', 'left');
    $column_clie = new TDataGridColumn('cliente->nome', 'Cliente', 'left');
    $column_qtd = new TDataGridColumn('quantidade', 'Quant.', 'left');
    $column_total = new TDataGridColumn('valor_total', 'Total', 'left');

    $column_dt_saida->setTransformer(function ($value, $object, $row) {
      // Formate a data para o formato desejado (por exemplo, 'd/m/Y')
      return date('d/m/Y', strtotime($value));
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

    //Criando ações para o datagrid
    $column_produto->setAction(new TAction([$this, 'onReload']), ['order' => 'produto_id']);
    $column_nf->setAction(new TAction([$this, 'onReload']), ['order' => 'nota_fiscal']);
    $column_dt_saida->setAction(new TAction([$this, 'onReload']), ['order' => 'data_saida']);
    $column_clie->setAction(new TAction([$this, 'onReload']), ['order' => 'cliente_id']);
    $column_qtd->setAction(new TAction([$this, 'onReload']), ['order' => 'quantidade']);
    $column_total->setAction(new TAction([$this, 'onReload']), ['order' => 'valor_total']);

    $action1 = new TDataGridAction(['SaidaForm', 'onEdit'], ['id' => '{id}', 'register_state' => 'false']);
    $action2 = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);

    //Adicionando a ação na tela
    $this->datagrid->addAction($action1, _t('Edit'), 'fa:edit blue');
    $this->datagrid->addAction($action2, 'Excluir/Cancelar', 'fa:trash-alt red');


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

      $retornoSaida = new Saida($id);
      if ($retornoSaida) {




        $retorno = Retorno_Cliente::where('saida_id', '=', $id)
          ->first();
        if ($retorno) {
          $retorno_id =  $retorno->id;

          // Verifica se existem saídas relacionadas a este estoque
          if ($this->hasRelatedOutbound($retorno_id)) {
            new TMessage('error', 'Não é possível excluir esta saida, pois existem vinculações.', $this->afterSaveAction);
          } else {
            try {

              // Exclua o estoque
              $saida = new Saida($id);
              $this->createDeleteMovement($saida);

              // Verifique se já existe uma entrada no mapa de estoque para esse produto
              $estoque = Estoque::where('id', '=', $retornoSaida->estoque_id)->load();
              $estoque = $estoque[0];
              $novaQuantidade = $estoque->quantidade + $retornoSaida->quantidade;
              $valor_atual = $estoque->valor_total + $retornoSaida->valor_total;
              $estoque->valor_total = $valor_atual;
              $estoque->quantidade = $novaQuantidade;
              $estoque->store();

             
              $saida->delete();


              // Recarregue a listagem
              $this->onReload();
              new TMessage('info', 'Registro excluído com sucesso.', $this->afterSaveAction);
            } catch (Exception $e) {
              new TMessage('error', $e->getMessage());
            }
          }
        } else {
          TTransaction::open('sample');

          // Exclua o estoque
          $saida = new Saida($id);
          $this->createDeleteMovement($saida);
          // Verifique se já existe uma entrada no mapa de estoque para esse produto
          $estoque = Estoque::where('id', '=', $retornoSaida->estoque_id)->load();
          $estoque = $estoque[0];
          $novaQuantidade = $estoque->quantidade + $retornoSaida->quantidade;
          $valor_atual = $estoque->valor_total + $retornoSaida->valor_total;
          $estoque->valor_total = $valor_atual;
          $estoque->quantidade = $novaQuantidade;
          $estoque->store();

          
          $saida->delete();

          new TMessage('info', 'Registro excluído com sucesso.', $this->afterSaveAction);

          // Recarregue a listagem
          $this->onReload();
        }
        TTransaction::close();


        $this->onReload();
      }
    }
  }

  private function createDeleteMovement($saida)
    {
      try{
        TTransaction::open('sample');

        //GRAVANDO MOVIMENTAÇÃO
        $mov = new Movimentacoes();
        $usuario_logado = TSession::getValue('userid');
        $descricao = 'Exclusão de Saida ' . $saida->produto_nome . ' - ' . $saida->quantidade . ' unidades - NF:' . $saida->nota_fiscal;

        $estoque = Estoque::where('produto_id', '=', $saida->produto_id)->first();

        $mov->data_hora = date('Y-m-d H:i:s');
        $mov->descricao = $descricao;
        $mov->produto_id = $saida->produto_id;
        $mov->responsavel_id = $usuario_logado;
        $mov->saldoEstoque = $estoque->valor_total ?? 0; 
        $mov->quantidade = $saida->quantidade ?? 0; 
        $mov->valor_total = $saida->valor_total ?? 0; 

        $mov->store(); 
        TTransaction::close();
      } catch (Exception $e) {
        new TMessage('error', $e->getMessage());
        TTransaction::rollback();
    }

    }
  public function onCancel($param)
  {
    if (isset($param['key'])) {
      // Obtém o ID do estoque a ser excluído


      $id = $param['key'];

      // Abre uma transação
      TTransaction::open('sample');


      TTransaction::close();
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
