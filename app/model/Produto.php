<?php

use Adianti\Database\TRecord;

/**
 * Grupo Active Record
 * @author  <your-name-here>
 */
class Produto extends TRecord
{
    const TABLENAME = 'produto';
    const PRIMARYKEY = 'id';
    const IDPOLICY =  'max'; // {max, serial}


    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id');
        parent::addAttribute('nome');
        parent::addAttribute('descricao');
        parent::addAttribute('unidade_id');
        parent::addAttribute('unidade_saida');
        parent::addAttribute('qt_correspondente');


        // Configurar os campos de timestamps
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');

        // Configurar os timestamps para atualização automática
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }


    public function get_unidade()
    {
        return Unidades_Medida::find($this->unidade_id);
    }
    public function get_unidadeDes()
    {
        return Unidades_Medida::find($this->unidade_saida);
    }
    public function store()
    {
        $this->updated_at = date('Y-m-d H:i:s');
        parent::store();
    }
}
