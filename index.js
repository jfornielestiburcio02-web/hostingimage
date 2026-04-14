const { Client, GatewayIntentBits } = require('discord.js');

const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMembers 
    ] 
});

// --- TUS DATOS ---
const TOKEN = "TU_TOKEN_AQUI";
const SERVER_ID = "ID_DEL_SERVER";
const ROLES_IDS = {
    POLICIA: "ID_POLICIA",
    MEDICO: "ID_MEDICO",
    BOMBERO: "ID_BOMBERO"
};

client.once('ready', () => {
    console.log(`Bot en Discloud listo como ${client.user.tag}`);
    
    const guild = client.guilds.cache.get(SERVER_ID);
    if (guild) {
        console.log(`Cacheando roles para el servidor: ${guild.name}`);
    }
});

// Ejemplo de comando rápido para ver si lee bien los roles
client.on('messageCreate', async (message) => {
    if (message.content === '!status_roles') {
        const guild = client.guilds.cache.get(SERVER_ID);
        const rPoli = guild.roles.cache.get(ROLES_IDS.POLICIA);
        const rMed = guild.roles.cache.get(ROLES_IDS.MEDICO);
        const rBomb = guild.roles.cache.get(ROLES_IDS.BOMBERO);

        message.reply(`Estado actual:\n👮 Policías: ${rPoli?.members.size}\n⚕️ Médicos: ${rMed?.members.size}\n泵 Bomberos: ${rBomb?.members.size}`);
    }
});

client.login(TOKEN);
