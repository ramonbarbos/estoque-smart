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
use Adianti\Widget\Form\TFormSeparator;
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

     
        // Criação do formulário
        $this->form = new BootstrapFormBuilder('form_cliente');
        $this->form->setFormTitle('Cliente');
        $this->form->setClientValidation(true);
        $this->form->setColumnClasses(3, ['col-sm-4', 'col-sm-4', 'col-sm-4']);

 
        // Criação de fields
        $id = new TEntry('id');
        $doc = new TEntry('nu_documento');
        $nome = new TEntry('nome');
        $sexo = new TCombo('sexo');
        $sexo->addItems( ['F' => 'Feminino', 'M' => 'Maculino' ] );
        $sexo->setValue('s'); 
        $tipo_documento = new TCombo('tp_cliente');
        $tipo_documento->addItems( ['F' => 'Física', 'J' => 'Jurídica' ] );
        $tipo_documento->setValue('s'); 
        $email = new TEntry('email');
        $fone = new TEntry('fone');
        $cep = new TEntry('cep');
        $logradouro = new TEntry('logradouro');
        $numero = new TEntry('numero');
        $complemento = new TEntry('complemento');
        $bairro = new TEntry('bairro');
        $estado = new TEntry('estado');
        $cidade = new TEntry('cidade');

        $cep->setExitAction( new TAction([ $this, 'onExitCEP']) );

        // Adicione fields ao formulário
        $this->form->addFields([new TLabel('Codigo')], [$id]);
        $this->form->addFields([new TLabel('Tipo')], [$tipo_documento],[new TLabel('CPF/CNPJ')], [$doc]);
        $this->form->addFields([new TLabel('Nome')], [$nome],[new TLabel('Sexo')], [$sexo]);
        $this->form->addFields([new TLabel('Email')], [$email],[new TLabel('Contato')], [$fone]);

        
        $this->form->addContent( [new TFormSeparator('Endereço')]);
        $this->form->addFields([new TLabel('CEP')], [$cep]);
        $this->form->addFields([new TLabel('Logradouro')], [$logradouro],[new TLabel('Numero')], [$numero]);
        $this->form->addFields([new TLabel('Complemento')], [$complemento],[new TLabel('Bairro')], [$bairro]);
        $this->form->addFields([new TLabel('Estado')], [$estado],[new TLabel('Cidade')], [$cidade]);

        $nome->addValidation('Nome', new TRequiredValidator);
        $doc->addValidation('Documento', new TRequiredValidator);
        $sexo->addValidation('Sexo', new TRequiredValidator);

        $id->setEditable(false);
        $id->setSize('100%');
        $doc->setSize('100%');
        $tipo_documento->setSize('100%');
        $nome->setSize('100%');
        $cep->setSize('100%');
        $logradouro->setSize('100%');
        $numero->setSize('100%');
        $complemento->setSize('100%');
        $bairro->setSize('100%');
        $cidade->setSize('100%');
        $cep->setMask('99.999-999');
        $fone->setMask('(99)99999-99999');

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
    public static function onExitCEP($param)
    {
        session_write_close();
        
        try
        {
            $cep = preg_replace('/[^0-9]/', '', $param['cep']);
            $url = 'https://viacep.com.br/ws/'.$cep.'/json/';
            
            $content = @file_get_contents($url);
            
            if ($content !== false)
            {
                $cep_data = json_decode($content);
                
                $data = new stdClass;
                if (is_object($cep_data) && empty($cep_data->erro))
                {
                  
                    
                    $data->logradouro  = $cep_data->logradouro;
                    $data->complemento = $cep_data->complemento;
                    $data->bairro      = $cep_data->bairro;
                    $data->estado      = $cep_data->uf;
                    $data->cidade      = $cep_data->localidade;
                    TForm::sendData('form_cliente', $data, false, true);
                    
                }
                else
                {
                    $data->logradouro  = '';
                    $data->complemento = '';
                    $data->bairro      = '';
                    $data->estado   = '';
                    $data->cidade   = '';
                    TForm::sendData('form_cliente', $data, false, true);
                    
                }
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    // Método fechar
    public function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
