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
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class FatorConvesaoForm extends TPage
{
  private $form;

  use Adianti\base\AdiantiStandardFormTrait;

  public function __construct()
  {
    parent::__construct();

    parent::setTargetContainer('adianti_right_panel');
    $this->setAfterSaveAction(new TAction(['FatorConvesaoList', 'onReload'], ['register_state' => 'true']));

    $this->setDatabase('sample');
    $this->setActiveRecord('Fator_Convesao');

    // Cria um array com as opções de escolha


    // Criação do formulário
    $this->form = new BootstrapFormBuilder('fator_conversao');
    $this->form->setFormTitle('Fator Convesao');
    $this->form->setClientValidation(true);
    $this->form->setColumnClasses(2, ['col-sm-5 col-lg-4', 'col-sm-7 col-lg-8']);



    // Criação de fields
    $id = new TEntry('id');
    $unidade_origem = new TDBUniqueSearch('unidade_origem', 'sample', 'Unidades_Medida', 'id', 'id');
    $unidade_origem->setMask('{nome} - {sigla}');
    $unidade_destino = new TDBUniqueSearch('unidade_destino', 'sample', 'Unidades_Medida', 'id', 'id');
    $unidade_destino->setMask('{nome} - {sigla}');

    //$fator = new TEntry('fator');

    // Adicione fields ao formulário
    $this->form->addFields([new TLabel('Codigo')], [$id]);
    $this->form->addFields([new TLabel('Origem')], [$unidade_origem]);
    $this->form->addFields([new TLabel('Destino')], [$unidade_destino]);
   // $this->form->addFields([new TLabel('Fator')], [$fator]);


    // Validação do campo Nome
    $unidade_origem->addValidation('Origem', new TRequiredValidator);
    $unidade_destino->addValidation('Destino', new TRequiredValidator);
   // $fator->addValidation('Fator', new TRequiredValidator);

    // Tornar o campo ID não editável
    $id->setEditable(false);

    // Tamanho dos campos
    $id->setSize('100%');
    $unidade_origem->setSize('100%');
    $unidade_origem->setMinLength(0);
    $unidade_destino->setSize('100%');
    $unidade_destino->setMinLength(0);

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

  


  

  // Método fechar
  public function onClose($param)
  {
    TScript::create("Template.closeRightPanel()");
  }
}
