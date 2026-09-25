-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 25/09/2026 às 20:57
-- Versão do servidor: 8.0.30
-- Versão do PHP: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `agendamento`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `avisos_email`
--

CREATE TABLE `avisos_email` (
  `id` bigint UNSIGNED NOT NULL,
  `evento` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conteudo` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `enviado_em` timestamp NULL DEFAULT NULL,
  `erro_tipo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `avisos_email`
--

INSERT INTO `avisos_email` (`id`, `evento`, `conteudo`, `estado`, `enviado_em`, `erro_tipo`, `created_at`, `updated_at`) VALUES
(1, 'a1fdf5de631a657febfb80302b5c5cb35f996212e9f9734ad1c49b0f8939ba24', '', 'enviado', '2026-09-24 21:35:05', NULL, '2026-09-24 21:34:57', '2026-09-24 21:35:05'),
(2, '0e4ef6b250ff92c49598de25d653b8b9dd403e807832c09fc4a52f7c0c3cd891', '', 'enviado', '2026-09-24 21:35:58', NULL, '2026-09-24 21:35:55', '2026-09-24 21:35:58'),
(3, 'b40be533b8a616c62ec47d83d3839cda133aa5f981d5a1d5c86bf539c4219bb9', '', 'enviado', '2026-09-25 02:54:15', NULL, '2026-09-25 02:54:11', '2026-09-25 02:54:15'),
(4, 'c242f919710cd899de02087d44d90818b255aa2be117820404c0dec6a800938f', '', 'enviado', '2026-09-25 04:00:38', NULL, '2026-09-25 04:00:34', '2026-09-25 04:00:38'),
(5, 'c0ecfe2b79e42ba0a9e1eb15d2656fec62ff88f65b1cc0fa5bc7535fe597c37a', '', 'enviado', '2026-09-25 04:00:59', NULL, '2026-09-25 04:00:55', '2026-09-25 04:00:59'),
(6, '855332d25460cac979359c6ce5e7cba0b177054d32fc79e3b695f63d2a7e5ec2', '', 'enviado', '2026-09-25 20:34:52', NULL, '2026-09-25 20:34:48', '2026-09-25 20:34:52'),
(7, 'd0af9f8082eddfff2915d145984ba35a2dfc0fb9d528a45978381414dff2e597', '', 'enviado', '2026-09-25 20:35:36', NULL, '2026-09-25 20:35:32', '2026-09-25 20:35:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-illuminate:queue:restart', 'i:1790285030;', 2105645030),
('laravel-cache-login-tentativa:1742aa93a536c742fdd33cf8f3e93b668df80f6fd4da3292d7c05c056858548d', 'b:1;', 1790305438),
('laravel-cache-login-tentativa:2dcfa219d979e18224061d1f0cc908c27c83a80a3371421b7e1c6545f5d7a8a8', 'b:1;', 1790305317),
('laravel-cache-login-tentativa:345fc8c6d0075becdeb6bbc02c833042b25d0894a72689ae915919980ef74537', 'b:1;', 1790305421),
('laravel-cache-login-tentativa:5da02caf69d5e8f4b5541eff3cbad346f2e01e505cf61806c3c31e4d3b0313d7', 'b:1;', 1790305370),
('laravel-cache-login-tentativa:5ea882c673378f2d21a07bf959cc5a330462a62508ef9879264e891d50240da8', 'b:1;', 1790363420),
('laravel-cache-login-tentativa:65dbabf07cceec0c9e72dedda9b22446e652431f4f570224361093720c771e37', 'b:1;', 1790286230),
('laravel-cache-login-tentativa:81e383453898fc7c29a27fa2cb1172cac03e23bf752cd38ae49566283bca5198', 'b:1;', 1790306512),
('laravel-cache-login-tentativa:ac6becfa0328c89cb7d92911993bfe7b5e3f275e1b34a7a3b080672de81de853', 'b:1;', 1790286601),
('laravel-cache-login-tentativa:d4cf1e17887790f04c537aff00bc18422ea66166de767ae8a58eda7678456936', 'b:1;', 1790363246),
('laravel-cache-login-tentativa:f4b08dc3203bb843ca31c9b58bc3902123cf27374d4583ef8f4626f06a7c280e', 'b:1;', 1790306416),
('laravel-cache-login-tentativa:ff90d522e0f2d96325fb9bac06365ad85004c5c671fee787862fd34f9158ccb7', 'b:1;', 1790363210),
('laravel-cache-senha-prontuario:356a192b7913b04c54574d18c28d46e6395428ab', 'i:1;', 1790370411),
('laravel-cache-senha-prontuario:356a192b7913b04c54574d18c28d46e6395428ab:timer', 'i:1790370410;', 1790370411);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `horarios`
--

CREATE TABLE `horarios` (
  `id` bigint UNSIGNED NOT NULL,
  `data` date NOT NULL,
  `hora` time NOT NULL,
  `disponivel` int NOT NULL DEFAULT '1',
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `matricula` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmado` tinyint(1) NOT NULL DEFAULT '0',
  `justificativa_cancelamento` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `token_cancelamento` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `horarios`
--

INSERT INTO `horarios` (`id`, `data`, `hora`, `disponivel`, `nome`, `matricula`, `confirmado`, `justificativa_cancelamento`, `created_at`, `updated_at`, `token_cancelamento`) VALUES
(170, '2026-09-24', '09:00:00', 1, NULL, NULL, 0, NULL, '2026-09-24 18:48:48', '2026-09-24 18:48:48', NULL),
(171, '2026-09-24', '10:00:00', 1, NULL, NULL, 0, NULL, '2026-09-24 18:48:48', '2026-09-24 18:48:48', NULL),
(172, '2026-09-24', '11:00:00', 1, NULL, NULL, 0, NULL, '2026-09-24 18:48:48', '2026-09-24 18:48:48', NULL),
(173, '2026-09-24', '14:00:00', 1, NULL, NULL, 0, NULL, '2026-09-24 18:48:48', '2026-09-24 18:48:48', NULL),
(174, '2026-09-24', '15:00:00', 1, NULL, NULL, 0, NULL, '2026-09-24 18:48:48', '2026-09-24 18:48:48', NULL),
(175, '2026-09-24', '15:52:00', 0, 'SAMUEL SANTOS DE LIMA ALVES', '20231180003', 1, NULL, '2026-09-24 18:48:48', '2026-09-24 21:39:35', NULL),
(176, '2026-09-25', '09:00:00', 1, NULL, NULL, 0, 'Teste', '2026-09-24 18:48:48', '2026-09-24 21:35:55', NULL),
(177, '2026-09-25', '10:00:00', 0, 'SAMUEL SANTOS DE LIMA ALVES', '20231180003', 0, NULL, '2026-09-24 18:48:48', '2026-09-25 02:54:11', 'Se00C22MHPgQFPiLyEZDcxkSDiZwaVvpcmTxCRVgR4jMyBCs5TMxy8ICS6hKFUw5'),
(178, '2026-09-25', '11:00:00', 1, NULL, NULL, 0, NULL, '2026-09-24 18:48:48', '2026-09-24 18:48:48', NULL),
(179, '2026-09-25', '14:00:00', 1, NULL, NULL, 0, NULL, '2026-09-24 18:48:48', '2026-09-24 18:48:48', NULL),
(180, '2026-09-25', '15:00:00', 1, NULL, NULL, 0, NULL, '2026-09-24 18:48:48', '2026-09-24 18:48:48', NULL),
(181, '2026-09-25', '16:00:00', 1, NULL, NULL, 0, NULL, '2026-09-24 18:48:48', '2026-09-24 18:48:48', NULL),
(182, '2026-09-30', '07:00:00', 1, NULL, NULL, 0, 'Testinho', '2026-09-25 04:00:21', '2026-09-25 19:01:52', NULL),
(183, '2026-09-29', '10:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(184, '2026-09-29', '11:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(185, '2026-09-29', '14:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(186, '2026-09-29', '15:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(187, '2026-09-29', '16:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(188, '2026-09-30', '09:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(189, '2026-09-30', '10:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(190, '2026-09-30', '11:00:00', 1, NULL, NULL, 0, 'Testando', '2026-09-25 04:00:21', '2026-09-25 20:35:32', NULL),
(191, '2026-09-30', '14:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(192, '2026-09-30', '15:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(193, '2026-09-30', '16:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(194, '2026-10-01', '09:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(195, '2026-10-01', '10:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(196, '2026-10-01', '11:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(197, '2026-10-01', '14:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(198, '2026-10-01', '15:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(199, '2026-10-01', '16:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(200, '2026-10-02', '09:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(201, '2026-10-02', '10:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(202, '2026-10-02', '11:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(203, '2026-10-02', '14:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(204, '2026-10-02', '15:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL),
(205, '2026-10-02', '16:00:00', 1, NULL, NULL, 0, NULL, '2026-09-25 04:00:21', '2026-09-25 04:00:21', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `jobs`
--

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(1, 'default', '{\"uuid\":\"bf1b41fe-5bba-4870-a7f0-04a8bd69b002\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:85;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:137:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/85?expires=1787623174&signature=3e1611da4c109629065503e1299cf47498234ca5d1d0ab46e49ccf7b6faa1b13\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"Samuel Santos de Lima Alves\\\";s:7:\\\"address\\\";s:34:\\\"samuelsantosdelimaalves3@gmail.com\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787450374,\"delay\":null}', 0, NULL, 1787450374, 1787450374),
(2, 'default', '{\"uuid\":\"9dd094db-e693-48f8-8ac2-c742574eabb7\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:81;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:137:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/81?expires=1787623220&signature=aab9993a564b17e8807c9b0b73a0e33979f47462502bc549c34ff81f2e0b5c8c\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:21:\\\"Ítalo Oliveira Silva\\\";s:7:\\\"address\\\";s:27:\\\"italosilvasba2019@gmail.com\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787450420,\"delay\":null}', 0, NULL, 1787450420, 1787450420),
(3, 'default', '{\"uuid\":\"f0b511b2-1590-438f-a201-655a591aafe9\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:86;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:137:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/86?expires=1787691219&signature=deb2dc63bcfa9bb893a938d0c02116a8bcff5b09aad6a0f8c14eafe58bb8bcd1\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:12:\\\"SAMUEL ALVES\\\";s:7:\\\"address\\\";s:29:\\\"20231180003@aluno.ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787518419,\"delay\":null}', 0, NULL, 1787518419, 1787518419),
(4, 'default', '{\"uuid\":\"58af9965-5ee4-46f7-bc74-f39888d3e254\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:86;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:137:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/86?expires=1787691371&signature=f0eae11e06d99f85f2f00cfb5728b0e7bc2c4a57e9adb37f2127e8bdb948a0cb\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:12:\\\"SAMUEL ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787518571,\"delay\":null}', 0, NULL, 1787518571, 1787518571),
(5, 'default', '{\"uuid\":\"37175691-3b98-4d47-840d-f8478789fff3\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:86;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:137:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/86?expires=1787691462&signature=2a55817e814411e1576d6c56987543c198bb565c2862cc242d023ac91b7b6668\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:12:\\\"SAMUEL ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787518662,\"delay\":null}', 0, NULL, 1787518662, 1787518662),
(6, 'default', '{\"uuid\":\"b6b1eb50-bcd3-43c6-9c91-e2ee8e4ea718\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:94;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:137:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/94?expires=1787691871&signature=fca51217bfe7104a9c970a2f4de9004fd41f3b4a1f4bb48358320fcd0c0f9a13\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:12:\\\"SAMUEL ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787519071,\"delay\":null}', 0, NULL, 1787519071, 1787519071),
(7, 'default', '{\"uuid\":\"5d338c3e-0d6f-45fa-8179-3c6e9de28dd1\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:110;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/110?expires=1787778967&signature=348817711c4e76037500de9686f2f0859c87a4b71af62f99d2d49f4a7c745817\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:32:\\\"Matheus Fagundes de Lima Pereira\\\";s:7:\\\"address\\\";s:23:\\\"20251180044@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787606167,\"delay\":null}', 0, NULL, 1787606167, 1787606167),
(8, 'default', '{\"uuid\":\"df45fd2b-1c6e-443d-ac45-5a34b5b0e201\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:110;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/110?expires=1787778981&signature=b2a7bd907483af19ce1d6584dc96fee7c7b60ae53c6c8889a24e4b840a1bed1c\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:32:\\\"Matheus Fagundes de Lima Pereira\\\";s:7:\\\"address\\\";s:23:\\\"20251180044@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787606181,\"delay\":null}', 0, NULL, 1787606181, 1787606181),
(9, 'default', '{\"uuid\":\"0d42c8ba-bf9a-4378-afc8-a15f79052e7c\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:110;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/110?expires=1787780126&signature=74f36095f72c266ca30efa806b8ee6addadbe8afbf6df978260c33b655ceda70\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:32:\\\"Matheus Fagundes de Lima Pereira\\\";s:7:\\\"address\\\";s:23:\\\"20251180044@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787607326,\"delay\":null}', 0, NULL, 1787607326, 1787607326),
(10, 'default', '{\"uuid\":\"75295506-8e86-46e8-ab96-2596cfe16dc5\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:111;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/111?expires=1787780149&signature=b2861e732f8c909b90726d7fc8caae7e0a195b8be44f1d32b2f78b4029ae1c2c\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787607349,\"delay\":null}', 0, NULL, 1787607349, 1787607349),
(11, 'default', '{\"uuid\":\"7435ceb0-7a3f-4693-b8d9-449f7b4d25ff\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:112;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/112?expires=1787780183&signature=9fbc96576f3486d8fba9c63c25e1ec276910eff228fd58406cb3ac9b1bb9e13f\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:24:\\\"SAMUEL OLIVEIRA JACONELY\\\";s:7:\\\"address\\\";s:27:\\\"samueljaconely621@gmail.com\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787607383,\"delay\":null}', 0, NULL, 1787607383, 1787607383),
(12, 'default', '{\"uuid\":\"5f883171-7538-4e8c-af3d-ad199b2759aa\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:135;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/135?expires=1787782974&signature=c4324621027ff65ac4e94c5844dafd712109d4241c8b9ce527d75ec97d5b2120\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787610174,\"delay\":null}', 0, NULL, 1787610174, 1787610174),
(13, 'default', '{\"uuid\":\"78d036cd-f0f9-47df-9545-dc52d72eab80\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:136;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/136?expires=1787788278&signature=9a7bd3167af058e811e1b30d7dd1197812b933e0f25a649fcad2c626a31aa715\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:24:\\\"SAMUEL OLIVEIRA JACONELY\\\";s:7:\\\"address\\\";s:27:\\\"samueljaconely621@gmail.com\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787615478,\"delay\":null}', 0, NULL, 1787615478, 1787615478),
(14, 'default', '{\"uuid\":\"2954f885-0098-474b-9cb8-ed1092a045c4\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:112;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/112?expires=1787795483&signature=914a5cd63d0df1b61b72bc1fffe819704db77642c37e9afc0c4bec1a3e6cafb8\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:24:\\\"SAMUEL OLIVEIRA JACONELY\\\";s:7:\\\"address\\\";s:27:\\\"samueljaconely621@gmail.com\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787622683,\"delay\":null}', 0, NULL, 1787622683, 1787622683),
(15, 'default', '{\"uuid\":\"192d5c4a-8659-486c-80dc-137cfbb25a6b\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:112;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/112?expires=1787795563&signature=4182618911a574501ca350799870fba9b11a6658256e481f1a515d518227964a\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:24:\\\"SAMUEL OLIVEIRA JACONELY\\\";s:7:\\\"address\\\";s:27:\\\"samueljaconely621@gmail.com\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787622763,\"delay\":null}', 0, NULL, 1787622763, 1787622763),
(16, 'default', '{\"uuid\":\"fbb2e8e6-43a2-4606-9861-d3b74a3e381c\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:113;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/113?expires=1787795627&signature=d87b357a87d7b73229bb952052a99ba36d7e52b5e834d6ff6b284ce777455f93\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:24:\\\"SAMUEL OLIVEIRA JACONELY\\\";s:7:\\\"address\\\";s:27:\\\"samueljaconely621@gmail.com\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787622827,\"delay\":null}', 0, NULL, 1787622827, 1787622827),
(17, 'default', '{\"uuid\":\"61b5789c-f4eb-4a4b-ac7b-1cbb65b0af01\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:137;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/137?expires=1787795706&signature=53ff9ce070aea826bc78ac8953ccfe403162fd295f4a10893bd53ddc8e690462\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787622906,\"delay\":null}', 0, NULL, 1787622906, 1787622906),
(18, 'default', '{\"uuid\":\"cff1b440-89cc-4770-b3a6-4233be39ea34\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:138;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/138?expires=1787796869&signature=57361f1f9073e8f148895d13552ac9b73c6ca114340062410678c39d5447d872\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:32:\\\"Matheus Fagundes de Lima Pereira\\\";s:7:\\\"address\\\";s:23:\\\"20251180044@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787624069,\"delay\":null}', 0, NULL, 1787624069, 1787624069),
(19, 'default', '{\"uuid\":\"876e012e-64e1-4749-9840-d712523fa371\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:110;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/110?expires=1787799399&signature=3d72f2bae84d2d0f7e12718ed385b2ef5be35503214f13612bb1a311ffe888a0\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:32:\\\"Matheus Fagundes de Lima Pereira\\\";s:7:\\\"address\\\";s:23:\\\"20251180044@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787626599,\"delay\":null}', 0, NULL, 1787626599, 1787626599),
(20, 'default', '{\"uuid\":\"26663ab4-0488-4f4d-88a2-411ca671932d\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:139;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/139?expires=1787803641&signature=05a1af0ab26befda0582e9e3e87f8c434d03a0324a62b1b0b1ca8f94ed29b2e2\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787630841,\"delay\":null}', 0, NULL, 1787630841, 1787630841),
(21, 'default', '{\"uuid\":\"98e5ba96-8f80-43a1-a09c-9010fe2afbc7\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:140;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/140?expires=1787873642&signature=23eb90ea811cd191c3b802882bf96a4890467cad38fabf978aead6824bd88cab\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:32:\\\"Matheus Fagundes de Lima Pereira\\\";s:7:\\\"address\\\";s:23:\\\"20251180044@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787700842,\"delay\":null}', 0, NULL, 1787700842, 1787700842),
(22, 'default', '{\"uuid\":\"3f229c1d-f1fc-48c2-a245-98530d5a92ab\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:142;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/142?expires=1787875131&signature=b9cc63eb4040cb68e6d4785f2e5c0b3885efd3a02f6557ee762244eaf08ce054\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787702331,\"delay\":null}', 0, NULL, 1787702331, 1787702331),
(23, 'default', '{\"uuid\":\"73f72ec9-1844-4720-b5d2-074a4894c7e8\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:142;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/142?expires=1787875142&signature=67d48ff55dc50e6e59015a9f83e1ef20bba8d3668d95fb7b44b6701c427a2df4\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787702342,\"delay\":null}', 0, NULL, 1787702342, 1787702342),
(24, 'default', '{\"uuid\":\"043f7075-481b-429a-97c8-f103c7584b60\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:142;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/142?expires=1787875171&signature=2f986901050015684aa3cec02a8df5045dbadaf692922e050e6ebf4b89e0f82b\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787702371,\"delay\":null}', 0, NULL, 1787702371, 1787702371),
(25, 'default', '{\"uuid\":\"0bfcbcb9-a7ec-4a26-b59a-9ce8d603bf4f\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:143;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/143?expires=1787875645&signature=44236e97cd99bb14de47bce71c7ab6949f9aba3167fc44a7c92329184d8a124c\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787702845,\"delay\":null}', 0, NULL, 1787702845, 1787702845),
(26, 'default', '{\"uuid\":\"f5587f86-3754-4002-9742-cf22660e0ff7\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:143;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/143?expires=1787875681&signature=e16f7da95cbc2bcc89258b9a1034e41240d4535eff085b591d15f529765fc845\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787702881,\"delay\":null}', 0, NULL, 1787702881, 1787702881),
(27, 'default', '{\"uuid\":\"90e1e1e5-6283-42db-a71a-a610dde7a172\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:143;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/143?expires=1787875712&signature=d62bc100074d9ae902d60ce41b08fb158cb9a78c037285baab422138f7ef45f0\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787702912,\"delay\":null}', 0, NULL, 1787702912, 1787702912),
(28, 'default', '{\"uuid\":\"8df1814d-8976-4338-84f5-8b688d7ef88c\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:144;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/144?expires=1787876381&signature=c80825be28ec138839688fe146ce542f80d9f2ab00b23c53729584d74ce1799c\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787703581,\"delay\":null}', 0, NULL, 1787703581, 1787703581),
(29, 'default', '{\"uuid\":\"6d0b5f5b-9cbd-4433-ba9e-14a283398f4a\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:116;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/116?expires=1787876740&signature=ada2abf20dc5fca29f2fc419b63de51d1e4a8ac96ef6a55d87676039c7165e18\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787703940,\"delay\":null}', 0, NULL, 1787703940, 1787703940);
INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(30, 'default', '{\"uuid\":\"a533f608-6747-4993-bb65-3510c810b999\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:116;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/116?expires=1787877047&signature=d2a422564045de85f26aa8a0830b3ec582fdd384fda866f372a2b15af7905858\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";s:27:\\\"SAMUEL SANTOS DE LIMA ALVES\\\";s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787704247,\"delay\":null}', 0, NULL, 1787704247, 1787704247),
(31, 'default', '{\"uuid\":\"f66b22cc-ed01-40df-9829-7d80d3b412de\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:116;s:9:\\\"relations\\\";a:2:{i:0;s:7:\\\"usuario\\\";i:1;s:11:\\\"agendamento\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/116?expires=1787878360&signature=efa0ab4c6d10566f18f1e780fc4ecf2547a2327678ccd56d9f6f03847eaa506c\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787705560,\"delay\":null}', 0, NULL, 1787705560, 1787705560),
(32, 'default', '{\"uuid\":\"b7b0787c-d261-4fc5-b4b4-1dbcee79bccc\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:116;s:9:\\\"relations\\\";a:2:{i:0;s:7:\\\"usuario\\\";i:1;s:11:\\\"agendamento\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/116?expires=1787878421&signature=6dc873fae60a5b53400587b8093202d4dca81476f16a23736488223892ba0554\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787705621,\"delay\":null}', 0, NULL, 1787705621, 1787705621),
(33, 'default', '{\"uuid\":\"dd593da4-c11a-412c-97a4-4a1856814590\",\"displayName\":\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Mail\\\\SendQueuedMailable\",\"command\":\"O:34:\\\"Illuminate\\\\Mail\\\\SendQueuedMailable\\\":17:{s:8:\\\"mailable\\\";O:35:\\\"App\\\\Mail\\\\ConfirmacaoAtendimentoMail\\\":4:{s:11:\\\"agendamento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Horario\\\";s:2:\\\"id\\\";i:116;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:15:\\\"urlCancelamento\\\";s:138:\\\"http:\\/\\/127.0.0.1:8000\\/cancelar-confirmar\\/116?expires=1787878909&signature=8b27a941f32b3c2a8e574ef6f59cc0c585601a61c3745f8b5c7e6385b66e1f9c\\\";s:2:\\\"to\\\";a:1:{i:0;a:2:{s:4:\\\"name\\\";N;s:7:\\\"address\\\";s:23:\\\"20231180003@ifba.edu.br\\\";}}s:6:\\\"mailer\\\";s:4:\\\"smtp\\\";}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"maxExceptions\\\";N;s:17:\\\"shouldBeEncrypted\\\";b:0;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:3:\\\"job\\\";N;}\",\"batchId\":null},\"createdAt\":1787706109,\"delay\":null}', 0, NULL, 1787706109, 1787706109);

-- --------------------------------------------------------

--
-- Estrutura para tabela `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_05_15_180024_create_usuarios_table', 1),
(5, '2026_05_15_180030_create_horarios_table', 1),
(6, '2026_05_15_180053_create_agendamentos_table', 1),
(7, '2026_05_15_180100_create_registros_atendimentos_table', 1),
(8, '2026_07_28_152856_create_prontuario_sessaos_table', 1),
(9, '2026_08_23_165315_add_suap_fields_to_users_table', 2),
(10, '2026_08_23_232700_add_turma_codigo_to_usuarios_table', 3),
(11, '2026_09_24_161756_add_token_cancelamento_to_horarios_table', 4),
(12, '2026_09_24_230000_create_controle_operacoes_tables', 5),
(13, '2026_09_25_010000_add_data_hora_atendimento_to_registros_atendimentos_table', 6),
(14, '2026_09_25_020000_remove_tabelas_legadas_users_e_agendamentos', 7);

-- --------------------------------------------------------

--
-- Estrutura para tabela `operacoes_http`
--

CREATE TABLE `operacoes_http` (
  `chave` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assinatura` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resposta` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `operacoes_http`
--

INSERT INTO `operacoes_http` (`chave`, `assinatura`, `resposta`, `created_at`) VALUES
('0cf679de0a0541b440c119b72e512f23b09e1fcfb906f505789b804bd7924cc8', 'f890cfed0f6e76017a9fb75706a7753d813d91b72ac278615480c1eff1d5c087', '{\"tipo\":\"json\",\"status\":200,\"dados\":{\"status\":\"success\",\"message\":\"Hor\\u00e1rio atualizado com sucesso.\"}}', '2026-09-25 19:01:52'),
('2c439b5e83ecb97f063cbaad8073d902f47df2d9a0eeab94814f368f420b4701', '2e7e0a4f28428aacb740ea0db4fd0b595304cd5078aa08be63e62651f9475570', '{\"tipo\":\"json\",\"status\":200,\"dados\":{\"status\":\"success\",\"message\":\"Atendimento conclu\\u00eddo e mantido no hist\\u00f3rico!\"}}', '2026-09-24 21:39:35'),
('379fc4ce716a3ff3fedeeb8c8f8abadc3db5660e093efa618fa8c7fe60bcc452', '86a3384938cf538d77dc6bb06bcf62f60acd2e869d0ff1e572cd49d77577f3ca', '{\"tipo\":\"json\",\"status\":200,\"dados\":{\"status\":\"success\",\"message\":\"24 hor\\u00e1rios customizados criados com sucesso.\"}}', '2026-09-25 04:00:21'),
('795769bcb76d7d21235f14427e36ce32283882318ac8d51cef53ee2444ae542b', '0c02cd665a2e71a9534e66358f24b72b55a85d08b86c39156f65d1d44d17f836', '{\"tipo\":\"json\",\"status\":200,\"dados\":{\"status\":\"success\",\"message\":\"Agendamento realizado! A confirma\\u00e7\\u00e3o ser\\u00e1 enviada por e-mail.\"}}', '2026-09-25 04:00:34'),
('8c70ec35dcfafb69b3a504ebaf40440487a417e5ac811f5dc50e9e1b0ef8672c', 'd4f003f1a75c6ec33778c7922f915c5fbc6117e5579b8ef82524279c4ded574b', '{\"tipo\":\"json\",\"status\":200,\"dados\":{\"status\":\"success\",\"message\":\"Agendamento cancelado. A confirma\\u00e7\\u00e3o ser\\u00e1 enviada por e-mail.\"}}', '2026-09-24 21:35:55'),
('9a6219c089b42e7b33c4846a5c6a0e007239bbd26a0d686b5cd02b1a47566308', '601242ea8e0a4fbc618217b774cdc694eef53d44de5505d2fc3d17a381e71ac4', '{\"tipo\":\"json\",\"status\":200,\"dados\":{\"status\":\"success\",\"message\":\"Agendamento realizado! A confirma\\u00e7\\u00e3o ser\\u00e1 enviada por e-mail.\"}}', '2026-09-25 20:34:48'),
('c555d28632409c22206faf44d9a85b9587df65ec303dd74c137b0e6e02d5de93', 'a9a7f380be13ec7ef29a3bcabc0ab81ad1a88d024a2af6c0a0163f7718f14706', '{\"tipo\":\"json\",\"status\":200,\"dados\":{\"status\":\"success\",\"message\":\"Agendamento realizado! A confirma\\u00e7\\u00e3o ser\\u00e1 enviada por e-mail.\"}}', '2026-09-25 02:54:11'),
('d6c333b183bdaf513caa0f4f8a776366dc49681706e63cca2cb8c4a41f09fd8b', '7c6bc14925992741b490b3b11c0805aacac49dc93239cf5d9ac4ba3fc4182425', '{\"tipo\":\"json\",\"status\":200,\"dados\":{\"status\":\"success\",\"message\":\"Agendamento cancelado. A confirma\\u00e7\\u00e3o ser\\u00e1 enviada por e-mail.\"}}', '2026-09-25 04:00:55'),
('fdd65724b5daffafcd5c28dc9253c4c1f50d950d4e4a745654ac6fd98088e4b7', '2d23259d59296cea01b09d4c28a04efccf04ba74cc6324386151674c6ba1a38c', '{\"tipo\":\"json\",\"status\":200,\"dados\":{\"status\":\"success\",\"message\":\"Agendamento realizado! A confirma\\u00e7\\u00e3o ser\\u00e1 enviada por e-mail.\"}}', '2026-09-24 21:34:57'),
('fdf4820040d96d7ee1b10eeb5bdf742eaa7b0637341fa2f07768cc89022e9a3b', 'e832e632af19f6a17edd9c43d5fca7c1f569654eb410d19002ef1a0425a5adc5', '{\"tipo\":\"json\",\"status\":200,\"dados\":{\"status\":\"success\",\"message\":\"Agendamento cancelado. A confirma\\u00e7\\u00e3o ser\\u00e1 enviada por e-mail.\"}}', '2026-09-25 20:35:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `prontuario_sessoes`
--

CREATE TABLE `prontuario_sessoes` (
  `id` bigint UNSIGNED NOT NULL,
  `aluno_id` bigint UNSIGNED NOT NULL,
  `horario_id` bigint UNSIGNED DEFAULT NULL,
  `anotacoes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_sessao` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `prontuario_sessoes`
--

INSERT INTO `prontuario_sessoes` (`id`, `aluno_id`, `horario_id`, `anotacoes`, `data_sessao`, `created_at`, `updated_at`) VALUES
(11, 24, NULL, 'eyJpdiI6InhEb255V3NhMC83SzNDajJWRWkvS0E9PSIsInZhbHVlIjoicTdlRlduTy9MYy9Cc2VqOGdTUDdIY0cvaTBrVEZhck1jWDRmSUNSVXZjcDBFajZZVGRhcllaRDVnbVk3QkFQaEtJVWRKQzBpalNRZTFkLzlRT2d2d0E9PSIsIm1hYyI6ImNlMTE1NzliOTYxZjM2ZjJmYzM1ODZlNTYyZjQ4ZDc4MWUwMzQ1NjU5NTMxMzUwZWY1ZjlkZWJjNDk5Yzg3YmQiLCJ0YWciOiIifQ==', '2026-09-24', '2026-09-24 18:53:38', '2026-09-24 18:53:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `registros_atendimentos`
--

CREATE TABLE `registros_atendimentos` (
  `id` bigint UNSIGNED NOT NULL,
  `id_horario_original` int NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `matricula` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `data_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `data_atendimento` date DEFAULT NULL,
  `hora_atendimento` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `registros_atendimentos`
--

INSERT INTO `registros_atendimentos` (`id`, `id_horario_original`, `nome`, `matricula`, `status`, `observacao`, `data_registro`, `created_at`, `updated_at`, `data_atendimento`, `hora_atendimento`) VALUES
(44, 176, 'SAMUEL SANTOS DE LIMA ALVES', '20231180003', 'Cancelado pelo Aluno', 'Cancelamento confirmado via link do e-mail', '2026-09-24 16:46:47', '2026-09-24 19:46:47', '2026-09-24 19:46:47', NULL, NULL),
(45, 176, 'SAMUEL OLIVEIRA JACONELY', '20241180017', 'Cancelado pela Psicóloga', 'Motivo: Te amo Jaco <3', '2026-09-24 16:50:54', '2026-09-24 19:50:54', '2026-09-24 19:50:54', NULL, NULL),
(46, 176, 'SAMUEL SANTOS DE LIMA ALVES', '20231180003', 'Cancelado pelo Aluno', 'Teste', '2026-09-24 18:35:55', '2026-09-24 21:35:55', '2026-09-24 21:35:55', NULL, NULL),
(47, 175, 'SAMUEL SANTOS DE LIMA ALVES', '20231180003', 'Realizado', 'Atendimento concluído com sucesso.', '2026-09-24 15:52:00', '2026-09-24 21:39:35', '2026-09-24 21:39:35', NULL, NULL),
(48, 182, 'SAMUEL SANTOS DE LIMA ALVES', '20231180003', 'Cancelado pelo Aluno', 'Testinho', '2026-09-25 01:00:55', '2026-09-25 04:00:55', '2026-09-25 04:00:55', '2026-09-29', '09:00:00'),
(49, 190, 'SAMUEL SANTOS DE LIMA ALVES', '20231180003', 'Cancelado pelo Aluno', 'Testando', '2026-09-25 17:35:32', '2026-09-25 20:35:32', '2026-09-25 20:35:32', '2026-09-30', '11:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('CQqK8ZVraXIN2bnGe3AIIrYv9OVnJR6KUovWHsiT', 24, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'YToxMTp7czo2OiJfdG9rZW4iO3M6NDA6ImlGMXBKVGY4ZkxndXhDSFY4WlVZVGpsdWpGTlBWWWpKRzNSWU12UWEiO3M6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjIxOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAiO3M6NToicm91dGUiO3M6MTI6ImFnZW5kYS5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI0O3M6ODoic3VhcF9qd3QiO3M6MjA1OiJleUowZVhBaU9pSktWMVFpTENKaGJHY2lPaUpJVXpJMU5pSjkuZXlKMWMyVnlYMmxrSWpveE1UZ3hNamtzSW1WdFlXbHNJam9pSWl3aWRYTmxjbTVoYldVaU9pSXlNREl6TVRFNE1EQXdNeUlzSW1WNGNDSTZNVGM1TURRME9UQXhNQ3dpYjNKcFoxOXBZWFFpT2pFM09UQXpOakkyTVRCOS5XSVBtbU1mTktBLTc2Xy1pUERKaGlYaWxGTWN0VU84bk53Vml0RllOTGVJIjtzOjEyOiJ1c3VhcmlvX3RpcG8iO3M6NToiYWx1bm8iO3M6MTI6InVzdWFyaW9fbm9tZSI7czoyNzoiU0FNVUVMIFNBTlRPUyBERSBMSU1BIEFMVkVTIjtzOjQ6Im5vbWUiO3M6Mjc6IlNBTVVFTCBTQU5UT1MgREUgTElNQSBBTFZFUyI7czo0OiJ0aXBvIjtzOjk6ImVzdHVkYW50ZSI7czo5OiJtYXRyaWN1bGEiO3M6MTE6IjIwMjMxMTgwMDAzIjtzOjU6ImVtYWlsIjtzOjMzOiJzYW11ZWxzYW50b3NkZWxpbWFhbHZlc0BnbWFpbC5jb20iO30=', 1790369329),
('lZWOQLFe89znqScixTHN157rSk5aFv68BbKpFdfT', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'YTo5OntzOjY6Il90b2tlbiI7czo0MDoiQkxOTnp2REtVSmpvNU1BWnJvVU5oVGlqdWVvRzA4MkNRc3RpcmtMTiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTEwOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYWdlbmRhL2V2ZW50b3M/ZW5kPTIwMjYtMTAtMTFUMDAlM0EwMCUzQTAwLTAzJTNBMDAmc3RhcnQ9MjAyNi0wOC0zMFQwMCUzQTAwJTNBMDAtMDMlM0EwMCI7czo1OiJyb3V0ZSI7czoxNDoiYWdlbmRhLmV2ZW50b3MiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MTI6InVzdWFyaW9fdGlwbyI7czo5OiJwc2ljb2xvZ2EiO3M6MTI6InVzdWFyaW9fbm9tZSI7czoxNDoiRWR1YXJkYSBDaGF2ZXMiO3M6NDoibm9tZSI7czoxNDoiRWR1YXJkYSBDaGF2ZXMiO3M6NDoidGlwbyI7czo5OiJwc2ljb2xvZ2EiO3M6MjE6InByb250dWFyaW9fYXV0b3JpemFkbyI7YjoxO30=', 1790369335),
('nnTMa8nXWnlQmZEm2rAW1YPaIFIgBuTeFHkI5ZgJ', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'YTo5OntzOjY6Il90b2tlbiI7czo0MDoiV2xKbUwwMDJsN1BpdGVTdHAwb2MwZzgyczBsVENYRkRGcG5DeWNzZSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTEwOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYWdlbmRhL2V2ZW50b3M/ZW5kPTIwMjYtMTAtMTFUMDAlM0EwMCUzQTAwLTAzJTNBMDAmc3RhcnQ9MjAyNi0wOC0zMFQwMCUzQTAwJTNBMDAtMDMlM0EwMCI7czo1OiJyb3V0ZSI7czoxNDoiYWdlbmRhLmV2ZW50b3MiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MTI6InVzdWFyaW9fdGlwbyI7czo5OiJwc2ljb2xvZ2EiO3M6MTI6InVzdWFyaW9fbm9tZSI7czoxNDoiRWR1YXJkYSBDaGF2ZXMiO3M6NDoibm9tZSI7czoxNDoiRWR1YXJkYSBDaGF2ZXMiO3M6NDoidGlwbyI7czo5OiJwc2ljb2xvZ2EiO3M6MTc6ImFsdW5vX2lkX3BlbmRlbnRlIjtzOjI6IjIzIjt9', 1790308915),
('ZhMJx2DCayXnIunrXpRkFXfQWq1728IOI9D99Qfn', 24, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'YToxMTp7czo2OiJfdG9rZW4iO3M6NDA6IjVSQWFkRm5sTE5Gd3lpNWRWU3MyNGR2dmtpT0ZiUXNobWdiZmwyR24iO3M6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjIxOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAiO3M6NToicm91dGUiO3M6MTI6ImFnZW5kYS5pbmRleCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI0O3M6ODoic3VhcF9qd3QiO3M6MjA1OiJleUowZVhBaU9pSktWMVFpTENKaGJHY2lPaUpJVXpJMU5pSjkuZXlKMWMyVnlYMmxrSWpveE1UZ3hNamtzSW1WdFlXbHNJam9pSWl3aWRYTmxjbTVoYldVaU9pSXlNREl6TVRFNE1EQXdNeUlzSW1WNGNDSTZNVGM1TURNNU1USXpPU3dpYjNKcFoxOXBZWFFpT2pFM09UQXpNRFE0TXpsOS5Rd0F6NDFIVUlRaFZNa0NiMGl5WFd2TWZKcmNRVEw0TzZPMTZrU29NZHZVIjtzOjEyOiJ1c3VhcmlvX3RpcG8iO3M6NToiYWx1bm8iO3M6MTI6InVzdWFyaW9fbm9tZSI7czoyNzoiU0FNVUVMIFNBTlRPUyBERSBMSU1BIEFMVkVTIjtzOjQ6Im5vbWUiO3M6Mjc6IlNBTVVFTCBTQU5UT1MgREUgTElNQSBBTFZFUyI7czo0OiJ0aXBvIjtzOjk6ImVzdHVkYW50ZSI7czo5OiJtYXRyaWN1bGEiO3M6MTE6IjIwMjMxMTgwMDAzIjtzOjU6ImVtYWlsIjtzOjMzOiJzYW11ZWxzYW50b3NkZWxpbWFhbHZlc0BnbWFpbC5jb20iO30=', 1790308860);

-- --------------------------------------------------------

--
-- Estrutura para tabela `travas_operacoes`
--

CREATE TABLE `travas_operacoes` (
  `nome` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `travas_operacoes`
--

INSERT INTO `travas_operacoes` (`nome`) VALUES
('agenda'),
('prontuario');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` bigint UNSIGNED NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `turma_codigo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('estudante','psicologa') COLLATE utf8mb4_unicode_ci NOT NULL,
  `matricula` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `turma_codigo`, `senha`, `tipo`, `matricula`, `password`, `data_nascimento`, `cidade`, `created_at`, `updated_at`) VALUES
(1, 'Eduarda Chaves', 'duda@gmail.com', NULL, '$2y$12$4GYxgsRlTz0sNbP76iT33.G//1MxVLqk3/PwQ3mej5sTrIN6WirBe', 'psicologa', '12345678', '', NULL, NULL, '2026-07-28 21:07:17', '2026-07-28 21:07:17'),
(23, 'SAMUEL OLIVEIRA JACONELY', 'samueljaconely621@gmail.com', '2024 - INF', '$2y$12$0yikN/Xl5VWDz2DV2.oDpek4mDCvb0uvihPGBBWpoYWfsuiQU6Oqe', 'estudante', '20241180017', NULL, NULL, NULL, '2026-09-23 17:28:48', '2026-09-24 19:48:08'),
(24, 'SAMUEL SANTOS DE LIMA ALVES', 'samuelsantosdelimaalves@gmail.com', '2023 - INF', '$2y$12$k7dWACKbD5ZP4Mdy6WPw/uXwJ2PJ7399dYzRe9ncqowtm8zoaSMmu', 'estudante', '20231180003', NULL, NULL, NULL, '2026-09-23 17:32:52', '2026-09-25 18:56:55'),
(25, 'Matheus Fagundes de Lima Pereira', 'fagundesmatheus486@gmail.com', '2025 - INF', '$2y$12$OCbFc19OQUbRdNkoh/2qs.KjCMGOECSqOrQuW2HhmUCqvWUovL8HO', 'estudante', '20251180044', NULL, NULL, NULL, '2026-09-23 17:34:34', '2026-09-23 17:34:34');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `avisos_email`
--
ALTER TABLE `avisos_email`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `avisos_email_evento_unique` (`evento`),
  ADD KEY `avisos_email_estado_index` (`estado`);

--
-- Índices de tabela `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Índices de tabela `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Índices de tabela `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Índices de tabela `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `horarios_token_cancelamento_unique` (`token_cancelamento`);

--
-- Índices de tabela `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Índices de tabela `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `operacoes_http`
--
ALTER TABLE `operacoes_http`
  ADD PRIMARY KEY (`chave`);

--
-- Índices de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Índices de tabela `prontuario_sessoes`
--
ALTER TABLE `prontuario_sessoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prontuario_sessoes_aluno_id_foreign` (`aluno_id`),
  ADD KEY `prontuario_sessoes_horario_id_foreign` (`horario_id`);

--
-- Índices de tabela `registros_atendimentos`
--
ALTER TABLE `registros_atendimentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Índices de tabela `travas_operacoes`
--
ALTER TABLE `travas_operacoes`
  ADD PRIMARY KEY (`nome`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuarios_email_unique` (`email`),
  ADD UNIQUE KEY `usuarios_matricula_unique` (`matricula`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `avisos_email`
--
ALTER TABLE `avisos_email`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=206;

--
-- AUTO_INCREMENT de tabela `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de tabela `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `prontuario_sessoes`
--
ALTER TABLE `prontuario_sessoes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `registros_atendimentos`
--
ALTER TABLE `registros_atendimentos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `prontuario_sessoes`
--
ALTER TABLE `prontuario_sessoes`
  ADD CONSTRAINT `prontuario_sessoes_aluno_id_foreign` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prontuario_sessoes_horario_id_foreign` FOREIGN KEY (`horario_id`) REFERENCES `horarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
