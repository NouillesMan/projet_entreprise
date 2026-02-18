<?php
// Configuration des options pour les listes déroulantes

return [
  // Systèmes d'exploitation
  'os' => [
    'Windows' => [
      'Windows 11',
      'Windows 10',
      'Windows Server 2022',
      'Windows Server 2019',
      'Windows Server 2016',
    ],
    'Linux' => [
      'Ubuntu 22.04 LTS',
      'Ubuntu 20.04 LTS',
      'Debian 12',
      'Debian 11',
      'CentOS 8',
      'Red Hat Enterprise Linux 9',
      'Fedora 39',
    ],
    'macOS' => [
      'macOS Sonoma 14',
      'macOS Ventura 13',
      'macOS Monterey 12',
      'macOS Big Sur 11',
    ],
    'Autre' => [
      'Autre',
    ],
  ],

  // Versions OS (utilisé si OS n'est pas dans la liste ci-dessus)
  'os_version' => [
    '23H2',
    '22H2',
    '21H2',
    '20H2',
    'LTS',
    'Standard',
    'Datacenter',
    'Pro',
    'Enterprise',
    'Home',
  ],

  // Marques de PC
  'marque' => [
    'Dell',
    'HP',
    'Lenovo',
    'Asus',
    'Acer',
    'MSI',
    'Apple',
    'Microsoft',
    'Toshiba',
    'Samsung',
    'Fujitsu',
    'Autre',
  ],

  // Modèles par marque
  'modele' => [
    'Dell' => [
      'Latitude 7490',
      'Latitude 7400',
      'Latitude 5420',
      'OptiPlex 7090',
      'OptiPlex 5090',
      'Precision 5560',
      'XPS 15',
      'XPS 13',
    ],
    'HP' => [
      'EliteBook 840 G8',
      'EliteBook 850 G7',
      'ProBook 450 G8',
      'ProDesk 600 G6',
      'ZBook 15 G8',
      'Pavilion 15',
    ],
    'Lenovo' => [
      'ThinkPad X1 Carbon Gen 9',
      'ThinkPad T14 Gen 2',
      'ThinkPad L15 Gen 2',
      'ThinkCentre M920q',
      'IdeaPad 3',
      'Legion 5',
    ],
    'Apple' => [
      'MacBook Pro 16"',
      'MacBook Pro 14"',
      'MacBook Air M2',
      'MacBook Air M1',
      'iMac 24"',
      'Mac mini M2',
    ],
  ],
];
