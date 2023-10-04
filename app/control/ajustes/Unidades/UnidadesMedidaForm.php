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
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Wrapper\BootstrapFormBuilder;

class UnidadesMedidaForm extends TPage
{
  private $form;

  use Adianti\base\AdiantiStandardFormTrait;

  public function __construct()
  {
    parent::__construct();

    parent::setTargetContainer('adianti_right_panel');
    $this->setAfterSaveAction(new TAction(['UnidadesMedidaList', 'onReload'], ['register_state' => 'true']));

    $this->setDatabase('sample');
    $this->setActiveRecord('Unidades_Medida');

    // Cria um array com as opções de escolha


    // Criação do formulário
    $this->form = new BootstrapFormBuilder('unidades_medida');
    $this->form->setFormTitle('Unidade de Medida');
    $this->form->setClientValidation(true);
    $this->form->setColumnClasses(2, ['col-sm-5 col-lg-4', 'col-sm-7 col-lg-8']);




    // Criação de fields
    $id = new TEntry('id');
    $nome = new TEntry('nome');
    $sigla = new TEntry('sigla');

    // Adicione fields ao formulário
    $this->form->addFields([new TLabel('Id')], [$id]);
    $this->form->addFields([new TLabel('Nome')], [$nome]);
    $this->form->addFields([new TLabel('Sigla')], [$sigla]);


    // Validação do campo Nome
    $nome->addValidation('Nome', new TRequiredValidator);
    $sigla->addValidation('Sigla', new TRequiredValidator);

    // Tornar o campo ID não editável
    $id->setEditable(false);

    // Tamanho dos campos
    $id->setSize('100%');
    $nome->setSize('100%');

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

  public function onEdit($param)
  {
    if (isset($param['key'])) {
      // Obtém o ID do cliente a ser excluído
      $id = $param['key'];

      TTransaction::open('sample');
      $produto = Produto::where('unidade_id', '=', $id)
        ->first();
      if ($produto) {
        $produto_id =  $produto->id;

        if ($this->hasRelatedOutbound($produto_id)) {
          $unidade = new Unidades_Medida($id);
          $this->form->setData($unidade);
          $this->form->getField('id')->setEditable(false);
          $this->form->getField('nome')->setEditable(false);
          $alert = new TAlert('warning', 'Não é possível editar este unidade de medida, pois já existem vinculações.');
          $alert->show();
        } else {
          $object = new Unidades_Medida($id);
          $this->form->setData($object);
        }
      } else {
        $object = new Unidades_Medida($id);
        $this->form->setData($object);
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
      $repository = new TRepository('Produto');
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
