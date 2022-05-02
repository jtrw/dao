IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[settings]') AND type in (N'U'))
DROP TABLE [dbo].[settings];

CREATE TABLE [dbo].[settings](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[caption] [varchar](128) NULL,
	[value] [varchar](255) NULL,
 CONSTRAINT [PK_settings] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY];


IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[site_contents]') AND type in (N'U'))
DROP TABLE [dbo].[site_contents];

CREATE TABLE [dbo].[site_contents](
    [id] [int] IDENTITY(1,1) NOT NULL,
    [id_parent] [int]
    [name] [varchar](128) NULL,
 CONSTRAINT [PK_site_contents] PRIMARY KEY CLUSTERED 
(
    [id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY];


IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[site_contents2settings]') AND type in (N'U'))
DROP TABLE [dbo].[site_contents2settings];

CREATE TABLE [dbo].[site_contents2settings](
    [id] [int] IDENTITY(1,1) NOT NULL,
    [id_site_content] [int],
    [id_setting] [int],
 CONSTRAINT [PK_site_contents2settings] PRIMARY KEY CLUSTERED 
(
    [id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY];