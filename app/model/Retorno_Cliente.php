<?php
use Adianti\Database\TRecord;

class Retorno_Cliente extends TRecord
{
    const TABLENAME = 'retorno_cliente';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        // Adicione os atributos normais
        parent::addAttribute('saida_id');
        parent::addAttribute('estoque_id');
        parent::addAttribute('produto_id');
        parent::addAttribute('data_retorno');
        parent::addAttribute('motivo');
        parent::addAttribute('nota_fiscal');
        parent::addAttribute('quantidade');
        parent::addAttribute('cliente_id');
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
    public function get_entrada()
    {
        return Entrada::find($this->entrada_id);
    }
    public function get_estoque()
    {
        return Estoque::find($this->estoque_id);
    }
    public function get_saida()
    {
        return Saida::find($this->saida_id);
    }

}
