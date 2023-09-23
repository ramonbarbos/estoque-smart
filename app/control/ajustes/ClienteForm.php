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
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TQuickForm;
use Adianti\Wrapper\BootstrapFormBuilder;

class ClienteForm extends TPage
{
    private $form;

    use Adianti\base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        parent::setTargetContainer('adianti_right_panel');
        $this->setAfterSaveAction(new TAction(['ClienteList', 'onReload'], ['register_state' => 'true']));

        $this->setDatabase('sample');
        $this->setActiveRecord('Cliente');

        // Cria um array com as opções de escolha
        $tipo_documento_options = [
            's' => 'Selecione',
            'j' => 'Juridico',
            'f' => 'Fisico',
        ];

        // Criação do formulário
        $this->form = new BootstrapFormBuilder('form_cliente');
        $this->form->setFormTitle('Cliente');
        $this->form->setClientValidation(true);
        $this->form->setColumnClasses(2, ['col-sm-5 col-lg-4', 'col-sm-7 col-lg-8']);

        


        // Adicione o campo de seleção ao formulário


        // Criação de fields
        $id = new TEntry('id');
        $doc = new TEntry('nu_documento');
        $nome = new TEntry('nome');
        $tipo_documento = new TCombo('tp_cliente');
        $tipo_documento->addItems($tipo_documento_options);
        $tipo_documento->setValue('f'); // Padrão para Pessoa Física
        $tipo_documento->setSize('100%');

        // Adicione fields ao formulário
        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel('Tipo de Documento')], [$tipo_documento]);
        $this->form->addFields([new TLabel('Doc.')], [$doc]);
        $this->form->addFields([new TLabel('Nome')], [$nome]);

        // Validação do campo Nome
        $nome->addValidation('Nome', new TRequiredValidator);

        // Tornar o campo ID não editável
        $id->setEditable(false);

        // Tamanho dos campos
        $id->setSize('100%');
        $doc->setSize('100%');
        $nome->setSize('100%');

        // Verifica o valor do campo $tipo_documento
        if ($tipo_documento->getValue() === 'j') {
            $doc->setMask('99.999.999/9999-99', true);
        } else if ($tipo_documento->getValue() === 'f') {
            $doc->setMask('999.999.999-99', true);
        }

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
        $saida = Saida::where('cliente_id', '=', $id)
                           ->first();
        if ($saida) {
          $retorno_id =  $saida->id;
  
          // Verifica se existem saídas relacionadas a este estoque
          if ($this->hasRelatedOutbound($retorno_id)) {
                 $entrada = new Cliente($id);
                $this->form->setData($entrada);
                $this->form->getField('id')->setEditable(false);
                $this->form->getField('tp_cliente')->setEditable(false);
                $this->form->getField('nu_documento')->setEditable(false);
                $this->form->getField('nome')->setEditable(false);
                 $alert = new TAlert('warning', 'Não é possível Editar este Cliente, pois já existem vinculações.');
                 $alert->show();
                 
          } else {
            try {
              // editar o cliente
              TTransaction::open('sample');
              $object = new Cliente($id);
              $this->form->setData($object);
  
              TTransaction::close();
  
            } catch (Exception $e) {
              new TMessage('error', $e->getMessage());
            }
          }
        } else {
            try {
                // editar o cliente
                TTransaction::open('sample');
                $object = new Cliente($id);
                $this->form->setData($object);
    
                TTransaction::close();
    
              } catch (Exception $e) {
                new TMessage('error', $e->getMessage());
              }
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
        $repository = new TRepository('Saida');
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
