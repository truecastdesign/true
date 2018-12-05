<?php

namespace True;

/**
 * Generic True Framework class
 * 
 * Extend this class to get access to standard methods without writing them each time.
 *
 * @author Dan Baldwin
 * @version 1.4.7
 */
 
 class GenericClass
 {
	 var $table = null;
	 var $searchNum = null;
	 var $listNum = null;
	 var $id = null;
	 var $DB = null;
	
	public function __construct($DB=null, $id=null)
 	{
 		$this->DB = $DB;
 		$this->id = $id;
 	}
 	
	public function set(array $set, $idField='id')
	{
		$fields = array_fill_keys($this->fields, 1);
		$id = $this->DB->set($this->table, array_intersect_key($set, $fields), $idField);
		$error = $this->DB->getErrors();
		if(!empty($error)) echo $error;
		return $id;
	}
	
	/**
	 * standard get method
	 *
	 * @param int|array $id id of record or array of ids or array of field match values
	 * @param array $options array('fields', 'idField', 'perPage', 'start', 'perPage', 'dir', 'sort', 'where', 'values')
	 * @return array row or multiple rows
	 * @author Daniel Baldwin
	 */
	public function get($id=null, $options=null)
	{
		if(!isset($options['fields'])) $options['fields']='*';
		if(!isset($options['idField'])) $options['idField']='id';
		
		if($id==null)
		{
			if(isset($options['perPage'])) $limit = ' LIMIT '.$options['start'].','.$options['perPage'];
			
			if(isset($options['sort'])) 
			{
				if(!isset($options['dir'])) $options['dir'] = 'ASC';
				$orderby = ' ORDER BY '.$options['sort'].' '.$options['dir'];
			}
			
			if(isset($options['where'])) $where = ' WHERE '.$options['where'];
			
			if(!isset($options['type'])) $options['type'] = '2dim';
			
			return $this->DB->get('SELECT '.$options['fields'].' FROM '.$this->table.$where.$orderby.$limit,$options['values'],$options['type']);
		}
		elseif(is_array($id) AND !empty($id[0]) AND !is_array($options['matchFields']))
		{
			if(isset($options['perPage'])) $limit = ' LIMIT '.$options['start'].','.$options['perPage'];
			
			if(isset($options['sort'])) 
			{
				if(!isset($options['dir'])) $options['dir'] = 'ASC';
				$orderby = ' ORDER BY '.$options['sort'].' '.$options['dir'];
			}
			else
			{
				$orderby = ' ORDER BY FIELD('.$options['idField'].','.implode(',',$id).')'.$options['sort'].' '.$options['dir'];
			}
			
			if(isset($options['where'])) $where = ' WHERE '.$options['where'];
			else $where = ' WHERE '.$options['idField'].' IN('.implode(',',$id).')';
			
			if(!isset($options['type'])) $options['type'] = '2dim';
			
			return $this->DB->get('SELECT '.$options['fields'].' FROM '.$this->table.$where.$orderby.$limit,$options['values'], $options['type']);
		}
		
		elseif($id==null AND isset($options['where']))
		{
			if(isset($options['perPage'])) $limit = ' LIMIT '.$options['start'].','.$options['perPage'];
		
			if(isset($options['sort'])) 
			{
				if(!$options['dir']) $options['dir'] = 'ASC';
				$orderby = ' ORDER BY '.$options['sort'].' '.$options['dir'];
			}
		
			$where = ' WHERE '.$options['where'];
			
			if(!isset($options['type'])) $options['type'] = '2dim';
			
			return $this->DB->get('SELECT '.$options['fields'].' FROM '.$this->table.$where.$orderby.$limit,$options['values'], $options['type']);
		}
			
		else
		{
			if(!isset($options['type'])) $options['type'] = 'array';
			
			return $this->DB->get('SELECT '.$options['fields'].' FROM '.$this->table.' WHERE '.$options['idField'].'=?',array($id),$options['type']);
		}
	}
		
	public function __set($key, $value)
	{
		if(is_numeric($this->id)) # update
		{
			return $this->DB->set($this->table, array('id'=>$this->id, $key=>$value));
		}	
		else
		{
			return $this->DB->set($this->table, array($key=>$value));
		}		
	}
	
	public function __get($key)
	{
		if(!is_numeric($this->id)) return false;
		return $this->DB->getValue('SELECT '.$key.' FROM '.$this->table.' WHERE id=?',array($this->id));
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function searchCount()
	{
		return $this->searchNum;
	}
	
	public function listCount()
	{
		return $this->listNum;
	}
	
	public function delete($id, $idField='id')
	{
		return $this->DB->delete($this->table,$id,$idField);
	}
	
 }
?>