<?php

use Adianti\Database\TRecord;

/**
 * Grupo Active Record
 * @author  <your-name-here>
 */
class Fator_Convesao extends TRecord
{
    const TABLENAME = 'fator_conversao';
    const PRIMARYKEY = 'id';
    const IDPOLICY =  'max'; // {max, serial}

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('unidade_origem');
        parent::addAttribute('unidade_destino');
        parent::addAttribute('fator');
       
    }

    public function get_unidadeOri()
    {
        return Unidades_Medida::find($this->unidade_origem);
    }
    public function get_unidadeDes()
    {
        return Unidades_Medida::find($this->unidade_destino);
    }
}
