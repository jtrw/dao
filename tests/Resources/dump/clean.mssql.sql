IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[settings]') AND type in (N'U'))
DROP TABLE [dbo].[settings];

IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[site_contents]') AND type in (N'U'))
DROP TABLE [dbo].[site_contents];

IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[site_contents2settings]') AND type in (N'U'))
DROP TABLE [dbo].[site_contents2settings];
