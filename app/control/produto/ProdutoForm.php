<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TAlert;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCheckList;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Form\TSpinner;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class ProdutoForm extends TPage
{
  private $form;

  use Adianti\base\AdiantiStandardFormTrait;

  public function __construct()
  {
    parent::__construct();

    parent::setTargetContainer('adianti_right_panel');
    $this->setAfterSaveAction(new TAction(['ProdutoList', 'onReload'], ['register_state' => 'true']));

    $this->setDatabase('sample');
    $this->setActiveRecord('Produto');

    // Cria um array com as opções de escolha


    // Criação do formulário
    $this->form = new BootstrapFormBuilder('form_Produto');
    $this->form->setFormTitle('Produto');
    $this->form->setClientValidation(true);
    // $this->form->setColumnClasses(2, ['col-sm-5 col-lg-4', 'col-sm-7 col-lg-8']);




    // Criação de fields
    $id = new TEntry('id');
    $nome = new TEntry('nome');
    $descricao = new TText('descricao');
    $criteria_unid = new TCriteria();
    $criteria_unid->setProperty('order', 'id');
    $unidade_id = new TDBUniqueSearch('unidade_id', 'sample', 'Unidades_Medida', 'id', 'id', null, $criteria_unid);
    $unidade_id->setChangeAction(new TAction([$this, 'onProductChange']));
    $unidade_id->setMask('{nome} - {sigla}');
    $valor_atual =  new TEntry('preco_unit');
    //-------------------------------------------------------------------
    $unidade_saida = new TDBUniqueSearch('unidade_saida', 'sample', 'Unidades_Medida', 'id', 'id', null, $criteria_unid);
    $unidade_saida->setMask('{nome} - {sigla}');
    $quantidade_correspondente     = new TSpinner('qt_correspondente');

    // Validação do campo Nome
    $nome->addValidation('Nome', new TRequiredValidator);
    $unidade_id->addValidation('Unidade', new TRequiredValidator);
    $id->setEditable(false);
    $id->setSize('37%');
    $nome->setSize('100%');
    $descricao->setSize('100%');
    $valor_atual->setSize('37%');
    $valor_atual->setEditable(false);
    $unidade_id->setSize('100%');
    $unidade_id->setMinLength(0);
    $valor_atual->setNumericMask(2, '.', '', true);
    $unidade_saida->setSize('10%');
    $unidade_saida->setMinLength(0);


    // Adicione fields ao formulário
    $this->form->addFields([new TLabel('Codigo')], [$id]);
    $this->form->addFields([new TLabel('Nome (*)', '#FF0000')], [$nome], [new TLabel('UND (*)', '#FF0000')], [$unidade_id]);
    $this->form->addFields([new TLabel('Descrição (*)', '#FF0000')], [$descricao]);
    $this->form->addFields([new TLabel('Valor Atual')], [$valor_atual]);


    // fildes 1 tab
    $subform = new BootstrapFormBuilder;
    $subform->setFieldSizes('100%');
    $subform->setProperty('style', 'border:none');

    $subform->appendPage('Unidade de Saida');
    $subform->addFields([new TLabel('Unidade')], [$unidade_saida], [new TLabel('Qtd. Corresp')], [$quantidade_correspondente]);
    $subform->layout = ['col-sm-2', 'col-sm-6', 'col-sm-4'];

    $this->form->addContent([$subform]);

    // Adicionar botão de salvar
    $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:plus green');
    $btn->class = 'btn btn-sm btn-primary';

    // Adicionar link para criar um novo registro
    $this->form->addActionLink(_t('New'), new TAction([$this, 'onEdit']), 'fa:eraser red');

    // Adicionar link para fechar o formulário
    $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

    // Vertical container
    $container = new TVBox;
    $container->style = 'width: 100%';
    $container->add($this->form);

    parent::add($container);
  }
  public function onSave($param)
  {
    try {
      TTransaction::open('sample');

      $data = $this->form->getData();
      $this->form->validate();



      $produto = new Produto;
      $produto->fromArray((array) $data);
      if ($this->validaUnidade($produto)) {

       
        if($this->correspondente($produto)){
          $produto->qt_correspondente = 1;
          new TMessage('info', 'Produto Salvo', $this->afterSaveAction);
          $produto->store();
        }else if(!$this->correspondente($produto)){
          if(empty($produto->qt_correspondente)){
            throw new Exception('É necessario informar a quantidade correspondente.');
          }
          new TMessage('info', 'Produto Salvo', $this->afterSaveAction);
          $produto->store();
        
        }

    

      } else {
        new TMessage('error', 'Conversão não é possível.');
      }




      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      $this->form->setData($this->form->getData());
      TTransaction::rollback();
    }
  }
  public function validaUnidade($param)
  {
    try {
      if ($param->unidade_id == $param->unidade_saida) {
        return true;
      } else if ($param->unidade_id != $param->unidade_saida) {

        $fatorConversao = Fator_Convesao::where('unidade_origem', '=', $param->unidade_id)
          ->where('unidade_destino', '=', $param->unidade_saida)
          ->first();

        if ($fatorConversao) {
          //  encontrado
          return true;
        } else {
          // não encontrado
          return false;
        }
      
      }

    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      $this->form->setData($this->form->getData());
    }
  }
  public function correspondente($param)
  {
   
     
      if ($param->unidade_id == $param->unidade_saida) {
        return true;
      } else if ($param->unidade_id != $param->unidade_saida) {

          return false;
      }
  
  }
  public static function onProductChange($params)
  {
    if (!empty($params['unidade_id'])) {
      try {
        $unidade_id = $params['unidade_id'];
        TTransaction::open('sample');
        TForm::sendData('form_Produto', (object) ['unidade_saida' => $unidade_id]);
        TTransaction::close();
      } catch (Exception $e) {
        new TMessage('error', $e->getMessage());
        TTransaction::rollback();
      }
    }
  }
  public function onEdit($param)
  {
    if (isset($param['key'])) {
      // Obtém o ID do cliente a ser excluído
      $id = $param['key'];

      TTransaction::open('sample');
      $itemEntrada = Item_Entrada::where('produto_id', '=', $id)->first();

      if ($itemEntrada) {
        $retorno_id =  $itemEntrada->id;

        // Verifica se existem saídas relacionadas a este estoque
        if ($this->hasRelatedOutbound($retorno_id)) {
          $entrada = new Produto($id);
          $this->form->setData($entrada);
          $this->form->getField('id')->setEditable(false);
          $this->form->getField('nome')->setEditable(false);
          $this->form->getField('descricao')->setEditable(false);
          $this->form->getField('unidade_id')->setEditable(false);
          $this->form->getField('unidade_id')->setEditable(false);
          $this->form->getField('unidade_saida')->setEditable(false);
          $this->form->getField('qt_correspondente')->setEditable(false);
          $alert = new TAlert('warning', 'Não é possível editar este produto, pois já existem vinculações.');
          $alert->show();
          $estoque = Estoque::where('produto_id', '=', $id)->first();
          $valor_atual = 0;
          if (isset($estoque->preco_unit)) {
            $valor_atual = $estoque->preco_unit;
          }
          TForm::sendData('form_Produto', (object) ['preco_unit' => $valor_atual]);
        } else {

          $estoque = Estoque::where('produto_id', '=', $id)->first();
          $valor_atual = 0;
          if (isset($estoque->preco_unit)) {
            $valor_atual = $estoque->preco_unit;
          }
          $object = new Produto($id);
          $this->form->setData($object);
          TForm::sendData('form_Produto', (object) ['preco_unit' => $valor_atual]);
        }
      } else {
        $estoque = Estoque::where('produto_id', '=', $id)->first();
        $valor_atual = 0;
        if (isset($estoque->preco_unit)) {

          $valor_atual = $estoque->preco_unit;
        }
        $object = new Produto($id);
        $this->form->setData($object);
        TForm::sendData('form_Produto', (object) ['preco_unit' => $valor_atual]);
      }
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


  // Método fechar
  public function onClose($param)
  {
    TScript::create("Template.closeRightPanel()");
  }
}
