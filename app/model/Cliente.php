<?php

use Adianti\Database\TRecord;

/**
 * Grupo Active Record
 * @author  <your-name-here>
 */
class Cliente extends TRecord
{
    const TABLENAME = 'cliente';
    const PRIMARYKEY = 'id';
    const IDPOLICY =  'max'; // {max, serial}


    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tp_cliente');
        parent::addAttribute('nu_documento');
        parent::addAttribute('nome');
        parent::addAttribute('sexo');
        parent::addAttribute('email');
        parent::addAttribute('fone');
        parent::addAttribute('cep');
        parent::addAttribute('logradouro');
        parent::addAttribute('numero');
        parent::addAttribute('complemento');
        parent::addAttribute('bairro');
        parent::addAttribute('estado');
        parent::addAttribute('cidade');
        // Configurar os campos de timestamps
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');

        // Configurar os timestamps para atualização automática
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }



    // Sobrescreva o método store para definir a data de atualização
    public function store()
    {
        $this->updated_at = date('Y-m-d H:i:s');
        parent::store();
    }
}
