<?php
use Adianti\Database\TRecord;

class Saida extends TRecord
{
    const TABLENAME = 'saida';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        // Adicione os atributos normais
        parent::addAttribute('tp_saida');
        parent::addAttribute('data_saida');
        parent::addAttribute('cliente_id');
        parent::addAttribute('obs');
        parent::addAttribute('valor_total');    
        parent::addAttribute('quantidade_total');    
        parent::addAttribute('status');    
      
    }

    public function get_produto()
    {
        return Produto::find($this->produto_id);
    }
    public function get_cliente()
    {
        return Cliente::find($this->cliente_id);
    }
    public function get_tipo()
    {
        return Tipo_Saida::find($this->tp_saida);
    }
}
