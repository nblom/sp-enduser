<?php

class NodeBackend extends Backend
{
	private $nodes = array();
	
	public function __construct($nodes)
	{
		$this->nodes = is_array($nodes) ? $nodes : array($nodes);
	}
	
	
	
	// A node backend is valid if it has at least one node
	public function isValid() { return count($this->nodes) > 0 && in_array('soap', get_loaded_extensions()); }
	
	// It's possible to call soap() if the node backend only has one soap node
	public function soap() { return count($this->nodes) == 1 ? $this->nodes[0]->soap(false) : NULL; }

	// Node backends support everything
	public function supportsHistory() { return true; }
	public function supportsQueue() { return true; }
	public function supportsQuarantine() { return true; }
	
	
	
	/**
	 * Send an asynchronous SOAP request to all nodes.
	 */
	public function soapCall($fname, $args, &$errors = array())
	{
		// Create an async SOAP client and stage an API call, note that
		// SoapFault exceptions can be thrown by either this call or the
		// retrieving one, so we have to handle either case.
		$clients = array();
		foreach ($this->nodes as $n => &$node)
		{
			$client = null;
			try {
				$client = $node->soap(true);
				$clients[$n] = $client;
			} catch (SoapFault $f) {
				// Don't explode if we can't connect
				$errors[] = $f->faultstring;
				$clients[$n] = NULL;
				continue;
			}
			
			try {
				call_user_func(array($client, $fname), $args[$n]);
			} catch (SoapFault $f) {
				$errors[] = $f->faultstring;
			}
		}
		
		// Dispatch all pending calls; if the curl extension is available, this
		// will be asynchronous, and will only take as long as the slowest node
		soap_dispatch();
		
		// Run the API call again to retrieve the resulting data
		$results = array();
		foreach ($clients as $n => &$c)
		{
			if ($c === NULL)
				continue;
			
			try {
				$results[$n] = call_user_func(array($clients[$n], $fname), $args[$n]);
			} catch (SoapFault $f) {
				$errors[] = $f->faultstring;
			}
		}
		
		return $results;
	}
	
	
	
	public function loadMailHistory($search, $size, $param, &$errors = array())
	{
		$queries = array();
		$restrict = $this->restrict_query('history');
		if ($restrict != '')
			$queries[] = $restrict;
		if ($search != '')
			$queries[] = $search;
		$restricted_search = implode(' && ', $queries);

		$params = array();
		foreach ($this->nodes as $n => &$node) {
			$params[] = array(
				'limit' => $size + 1,
				'filter' => $restricted_search,
				'offset' => $param[$n]['offset']
			);
		}
		$results = $this->soapCall('mailHistory', $params, $errors);
		
		$timesort = array();
		foreach ($results as $n => $data)
			if (is_array($data->result->item))
				foreach ($data->result->item as $item)
					$timesort[$item->msgts0][] = array('id' => $n, 'type' => 'history', 'data' => $item);
		
		return $timesort;
	}
	
	public function loadMailQueue($search, $size, $param, &$errors = array())
	{
		$queries = array();
		$restrict = $this->restrict_query('queue');
		if ($restrict != '')
			$queries[] = $restrict;
		if ($search != '')
			$queries[] = $search;
		$restricted_search = implode(' && ', $queries);

		$params = array();
		foreach ($this->nodes as $n => &$node) {
			$params[] = array(
				'limit' => $size + 1,
				'filter' => $restricted_search,
				'offset' => $param[$n]['offset']
			);
		}
		$results = $this->soapCall('mailQueue', $params, $errors);
		
		$timesort = array();
		foreach ($results as $n => $data)
			if (is_array($data->result->item))
				foreach ($data->result->item as $item)
					$timesort[$item->msgts0][] = array('id' => $n, 'type' => 'queue', 'data' => $item);
		
		return $timesort;
	}

	public function getMailInQueue($search, &$errors = array())
	{
		return $this->getMailIn_('mailQueue', $search, $errors);
	}

	public function getMailInHistory($search, &$errors = array())
	{
		return $this->getMailIn_('mailHistory', $search, $errors);
	}

	public function getMailInQueueOrHistory($search, &$errors, &$type)
	{
		$type = 'queue';
		$mail = $this->getMailInQueue($search, $errors);
		if (!$mail || $errors) {
			$mail = $this->getMailInHistory($search, $errors);
			$type = 'history';
		}
		return $mail;
	}

	private function getMailIn_($source, $search, &$errors = array())
	{
		if (empty($this->nodes))
			return NULL;

		$queries = array();
		$restrict = $this->restrict_query($source == 'mailHistory' ? 'history' : 'queue');
		if ($restrict != '')
			$queries[] = $restrict;
		if ($search != '')
			$queries[] = $search;
		$restricted_search = implode(' && ', $queries);

		$params = array();
		foreach ($this->nodes as $n => &$node) {
			$params[] = array(
					'limit' => 2,
					'filter' => $restricted_search,
					'offset' => array()
					);
		}
		$results = $this->soapCall($source, $params, $errors);

		$mail = array();
		foreach ($results as $n => $data)
			if (is_array($data->result->item))
				$mail = array_merge($mail, $data->result->item);
		return count($mail) == 1 && empty($errors) ? $mail[0] : NULL;
	}

	// Returns the SOAP HQL syntax for $access's access rights
	private function restrict_query($type)
	{
		$settings = Settings::Get();
		$access = Session::Get()->getAccess();

		$globalfilter = "";
		if (count($settings->getQuarantineFilter()) > 0 && $type != 'history')
		{
			foreach ($settings->getQuarantineFilter() as $q)
			{
				if ($globalfilter != "")
					$globalfilter .= " or ";
				$globalfilter .= "quarantine=$q";
			}
			$globalfilter .= ' or not action=QUARANTINE ';
		}

		$pattern = $settings->getFilterPattern();

		$filter = "";
		if (is_array($access['domain'])) {
			foreach ($access['domain'] as $domain) {
				if ($filter != "")
					$filter .= " or ";
				$filter .= str_replace(array('{from}', '{to}'), array("from~%@$domain", "to~%@$domain"), $pattern);
			}
		}

		if (is_array($access['mail'])) {
			foreach ($access['mail'] as $mail) {
				if ($filter != "")
					$filter .= " or ";
				$filter .= str_replace(array('{from}', '{to}'), array("from=$mail", "to=$mail"), $pattern);
			}
		}
		
		if (is_array($access['server'])) {
			foreach ($access['server'] as $server) {
				if ($filter != "")
					$filter .= " or ";
				$filter .= str_replace(array('{from}', '{to}'), array("server=$server", "server=$server"), $pattern);
			}
		}
		
		if (is_array($access['transport'])) {
			foreach ($access['transport'] as $transport) {
				if ($filter != "")
					$filter .= " or ";
				$filter .= str_replace(array('{from}', '{to}'), array("transport=$transport", "transport=$transport"), $pattern);
			}
		}
		return $globalfilter.($globalfilter?" && ":"").$filter;
	}
}
