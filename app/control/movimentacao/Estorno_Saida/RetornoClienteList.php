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

class RetornoClienteList extends TPage
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
    $this->setActiveRecord('Retorno_Cliente');
    $this->setDefaultOrder('id', 'asc');
    $this->setLimit(10);

    $this->addFilterField('data_retorno', '=', 'data_retorno');
    $this->addFilterField('cliente_id', '=', 'cliente_id');


    //Criação do formulario 
    $this->form = new BootstrapFormBuilder('form_search_retorno');
    $this->form->setFormTitle('Estorno do Saida');

    //Criação de fields
    $data = new TDate('data_saida');
    $cliente = new TDBUniqueSearch('cliente_id', 'sample', 'Cliente', 'id', 'nome');
    $cliente->setMinLength(0);

    //Add filds na tela
    $this->form->addFields([new TLabel('Data')], [$data]);
    $this->form->addFields([new TLabel('Cliente')], [$cliente]);

    //Tamanho dos fields
    $data->setSize('50%');
    $cliente->setSize('100%');

    $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

    //Adicionar field de busca
    $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
    $btn->class = 'btn btn-sm btn-primary';
    $this->form->addActionLink(_t('New'), new TAction(['RetornoClienteForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');

    //Criando a data grid
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->style = 'width: 100%';

    //Criando colunas da datagrid
    $column_id = new TDataGridColumn('id', 'Codigo', 'left');
    $column_dt_retorno = new TDataGridColumn('data_retorno', 'Data', 'left');
    $column_clie = new TDataGridColumn('cliente->nome', 'Cliente', 'left');
    $column_status = new TDataGridColumn('status', 'Status', 'left');
    $column_total = new TDataGridColumn('valor_total', 'Total', 'left');

    $column_dt_retorno->setTransformer(function ($value, $object, $row) {
      // Formate a data para o formato desejado (por exemplo, 'd/m/Y')
      return date('d/m/Y', strtotime($value));
    });

    $formato_valor = function ($value) {
      if (is_numeric($value)) {
        return 'R$ ' . number_format($value, 2, ',', '.');
      }
      return $value;
    };
    $column_total->setTransformer($formato_valor);

    $column_status->setTransformer(function ($value, $object, $row) {
      return ($value == 1) ? "<span style='color:green'>Estornado</span>" : "<span style='color:red'>Cancelado</span>";
    });
    //add coluna da datagrid
    $this->datagrid->addColumn($column_id);
    $this->datagrid->addColumn($column_dt_retorno);
    $this->datagrid->addColumn($column_status);
    $this->datagrid->addColumn($column_clie);
    $this->datagrid->addColumn($column_total);

    //Criando ações para o datagrid
    $column_dt_retorno->setAction(new TAction([$this, 'onReload']), ['order' => 'data_retorno']);
    $column_clie->setAction(new TAction([$this, 'onReload']), ['order' => 'cliente_id']);
    $column_total->setAction(new TAction([$this, 'onReload']), ['order' => 'valor_total']);

    $action1 = new TDataGridAction(['RetornoClienteForm', 'onEdit'], ['id' => '{id}', 'register_state' => 'false']);
    $action2 = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
    $action3 = new TDataGridAction([$this, 'onCancel'], ['id' => '{id}']);

    //Adicionando a ação na tela
    $this->datagrid->addAction($action1, _t('Edit'), 'fa:edit blue');
    $this->datagrid->addAction($action2, 'Excluir', 'fa:trash-alt red');
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

        $retorno = new Retorno_Cliente($id);

        if ($retorno) {
          if ($retorno->status == 1) {
            $retorno->status = 0;

            $saida = new Saida($retorno->saida_id);
            $saida->status = 1;
            $saida->store();

            $this->cancelEstoque($retorno);
            $retorno->store();

            TTransaction::close();

            new TMessage('info', 'Estorno Cancelado.', $this->afterSaveAction);
            $this->onReload([]);
          } else {
            throw new Exception("A retorno já está cancelada.");
          }
        } else {
          throw new Exception("Estorno não encontrada.");
        }
      }
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      TTransaction::rollback();
    }
  }
  private function cancelEstoque($retorno)
  {
    try {
      TTransaction::open('sample');

      $itens = Item_Retorno_Cliente::where('retorno_id', '=', $retorno->id)->load();

      foreach ($itens as $item) {
        $produto_id = $item->produto_id;
        $quantidade = $item->quantidade_retorno;
        $totalValor = $item->total;

        $estoque = Estoque::where('produto_id', '=', $produto_id)->first();

        if ($estoque) {
          if ($estoque->quantidade + $quantidade < 0) {
            throw new Exception("A quantidade no estoque do produto $produto_id não pode ser negativa.");
          }

          if ($estoque->valor_total + $totalValor < 0) {
            throw new Exception("O valor total no estoque do produto $produto_id não pode ser negativo.");
          }

          $estoque->quantidade -= $quantidade;
          $estoque->valor_total -= $totalValor;

          if ($estoque->quantidade == 0) {
            $estoque->preco_unit = 0;
          } else {
            // Calcule o novo preço unitário com base no valor total e na quantidade restante
            $estoque->preco_unit = $estoque->valor_total / $estoque->quantidade;
          }

          $estoque->store();
          $this->atualizarQuantidadeTotalSaida($retorno);
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
  public function onDelete($param)
  {
    if (isset($param['key'])) {
      // Obtém o ID do estoque a ser excluído
      $id = $param['key']; //ID da saida
      TTransaction::open('sample');

      $retorno = new Retorno_Cliente($id);

      if ($retorno->status == 0) {
        if ($retorno) {
          $this->deleteMovement($retorno);
          Item_Retorno_Cliente::where('retorno_id', '=', $retorno->id)->delete();
          $retorno->delete();
          new TMessage('info', 'Estorno deletada.', $this->afterSaveAction);
          $this->onReload([]);
        }
      } else {
        new TMessage('warning', 'É necessario o estorno está cancelado.', $this->afterSaveAction);
      }
    }
  }
  private function atualizarQuantidadeTotalSaida($info)
  {
      $saida = new Saida($info->saida_id);
      $quantidadeTotalOriginal = $saida->quantidade_total;
  
      $itensRetorno = Item_Retorno_Cliente::where('retorno_id', '=', $info->id)->load();
      $novaQuantidadeTotal = $quantidadeTotalOriginal;
      
      foreach ($itensRetorno as $itemRetorno) {
          $novaQuantidadeTotal += $itemRetorno->quantidade_retorno;
      }
      
      $saida->quantidade_total = $novaQuantidadeTotal;
      $saida->store();
  }

  private function atualizarStatusSaida($info)
  {
      $saida = new Saida($info->saida_id);
      $quantidadeTotal = $saida->quantidade_total;
  
      if ($quantidadeTotal == 0) {
          $saida->status = 2;  
          $saida->store();
      }
  }
  
  private function deleteMovement($retorno)
  {
    //GRAVANDO MOVIMENTAÇÃO
    $mov = new Movimentacoes();
    $usuario_logado = TSession::getValue('userid');
    $descricao = 'Estorno Deletado';

    $item = Item_Retorno_Cliente::where('retorno_id', '=', $retorno->id)->first();
    @$estoque = Estoque::where('produto_id', '=', $item->produto_id)->first();

    $mov->data_hora = date('Y-m-d H:i:s');
    $mov->descricao = $descricao;
    @$mov->produto_id = $estoque->produto_id;
    $mov->preco_unit = $item->preco_unit;
    $mov->responsavel_id = $usuario_logado;
    $mov->saldo_anterior = $estoque->valor_total ?? 0;
    $mov->quantidade = $item->quantidade_retorno ?? 0;
    $mov->valor_total = $item->valor_total ?? 0;

    $mov->store();
  }
  private function cancelMovement($info)
  {
    try {
      TTransaction::open('sample');
      //GRAVANDO MOVIMENTAÇÃO
      $mov = new Movimentacoes();
      $estoque = Estoque::where('produto_id', '=', $info->produto_id)->first();

      $usuario_logado = TSession::getValue('userid');
      $desc =  'Estorno Cancelado.';
      $mov->data_hora = date('Y-m-d H:i:s');
      $mov->descricao = $desc;
      $mov->preco_unit = $info->preco_unit;
      $mov->produto_id = $info->produto_id;
      $mov->responsavel_id = $usuario_logado;
      $mov->saldo_anterior = $estoque->valor_total ?? 0;
      $mov->quantidade = $info->quantidade_retorno ?? 0;
      $mov->valor_total = $info->valor_total ?? 0;


      $mov->store();
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      TTransaction::rollback();
    }
  }
}
