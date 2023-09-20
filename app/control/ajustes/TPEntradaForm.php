<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Container\TVBox;
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

class TPEntradaForm extends TPage
{
    private $form;

    use Adianti\base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        parent::setTargetContainer('adianti_right_panel');
        $this->setAfterSaveAction(new TAction(['TPEntradaList', 'onReload'], ['register_state' => 'true']));

        $this->setDatabase('sample');
        $this->setActiveRecord('Tipo_Entrada');

        // Cria um array com as opções de escolha
      

        // Criação do formulário
        $this->form = new BootstrapFormBuilder('tipo_entrada');
        $this->form->setFormTitle('Tipo_Entrada');
        $this->form->setClientValidation(true);
        $this->form->setColumnClasses(2, ['col-sm-5 col-lg-4', 'col-sm-7 col-lg-8']);

    
      

        // Criação de fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');

        // Adicione fields ao formulário
        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Nome')], [$nome]);

       
        // Validação do campo Nome
        $nome->addValidation('Nome', new TRequiredValidator);
        
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

    // Método para tratar a mudança no campo de seleção
    public  static function onChangeDocumento($param)
    {
       
    }

    // Método fechar
    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
