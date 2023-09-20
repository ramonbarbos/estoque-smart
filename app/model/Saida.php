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
        parent::addAttribute('estoque_id');
        parent::addAttribute('produto_id');
        parent::addAttribute('data_saida');
        parent::addAttribute('quantidade');
        parent::addAttribute('quant_retirada');
        parent::addAttribute('cliente_id');
        parent::addAttribute('nota_fiscal');
        parent::addAttribute('preco_unit');
        parent::addAttribute('valor_total');
      
    }

    public function get_produto()
    {
        return Produto::find($this->produto_id);
    }
    public function get_cliente()
    {
        return Cliente::find($this->cliente_id);
    }
}
