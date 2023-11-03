import axios from "@/utils/axios";

const getPurchases = () => {
    return new Promise(resolve => {
        axios.get('tree-planting').then(response => {
            resolve(response.data.data);
        })
    })
}

export {
    getPurchases
}